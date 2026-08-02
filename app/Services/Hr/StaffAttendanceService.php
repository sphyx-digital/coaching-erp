<?php

namespace App\Services\Hr;

use App\Models\Staff;
use App\Models\StaffAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Staff (employee) attendance: day-level status with half-day support, and the
 * monthly summary that drives payroll. Statuses: present, absent, half_day,
 * leave, holiday, week_off.
 */
class StaffAttendanceService
{
    public const STATUSES = ['present', 'absent', 'half_day', 'leave', 'holiday', 'week_off'];

    public function mark(Staff $staff, string $date, string $status, ?string $remarks = null): StaffAttendance
    {
        abort_unless(in_array($status, self::STATUSES, true), 422);

        $normalized = CarbonImmutable::parse($date)->toDateString();
        $attrs = [
            'institute_id' => $staff->institute_id,
            'branch_id' => $staff->branch_id,
            'status' => $status,
            'remarks' => $remarks,
        ];

        $row = StaffAttendance::where('staff_id', $staff->id)->where('date', $normalized)->first();
        if ($row) {
            $row->update($attrs);

            return $row;
        }

        return StaffAttendance::create($attrs + ['staff_id' => $staff->id, 'date' => $normalized]);
    }

    /**
     * Summarise a staff member's month for payroll.
     *
     * @return array{days_in_month:int,present:int,absent:int,half_day:int,leave:int,holiday:int,week_off:int,marked:int,unpaid_days:float}
     */
    public function summary(Staff $staff, string $month): array
    {
        $start = CarbonImmutable::parse($month)->startOfMonth();
        $end = $start->endOfMonth();
        $daysInMonth = $start->daysInMonth;

        $rows = StaffAttendance::where('staff_id', $staff->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $r) {
            $counts[$r->status] = ($counts[$r->status] ?? 0) + 1;
        }

        $allowedPaidLeaves = (int) client_setting('payroll_paid_leaves', 2);
        $excessLeave = max(0, $counts['leave'] - $allowedPaidLeaves);
        $unpaid = $counts['absent'] + $excessLeave + 0.5 * $counts['half_day'];

        return [
            'days_in_month' => $daysInMonth,
            'present' => $counts['present'],
            'absent' => $counts['absent'],
            'half_day' => $counts['half_day'],
            'leave' => $counts['leave'],
            'holiday' => $counts['holiday'],
            'week_off' => $counts['week_off'],
            'marked' => $rows->count(),
            'unpaid_days' => round($unpaid, 1),
        ];
    }

    /**
     * Attendance for a month keyed by staff_id then day-of-month.
     *
     * @return array<int,array<int,string>>
     */
    public function matrix(string $month): array
    {
        $start = CarbonImmutable::parse($month)->startOfMonth();
        $end = $start->endOfMonth();

        $out = [];
        StaffAttendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->each(function ($r) use (&$out) {
                $out[$r->staff_id][(int) Carbon::parse($r->date)->day] = $r->status;
            });

        return $out;
    }
}
