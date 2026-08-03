<?php

namespace App\Services\Search;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Course;
use App\Models\Enquiry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;

/**
 * App-wide search for the command palette. Each group is permission-gated and
 * returns deep links that open the record's detail on the target screen.
 * Branch-scoped models (student, enquiry, batch, invoice, payment) inherit the
 * user's branch scope automatically via their global scopes.
 */
class SearchService
{
    /**
     * @return array<int,array{type:string,icon:string,items:array<int,array{label:string,sub:string,url:string}>}>
     */
    public function search(User $user, string $term, int $perGroup = 5): array
    {
        $term = trim($term);
        if (mb_strlen($term) < 2) {
            return [];
        }
        $like = '%'.$term.'%';
        $groups = [];

        if ($user->can('admission.view')) {
            $items = Student::query()->with('branch')
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('admission_number', 'like', $like)->orWhere('phone', 'like', $like))
                ->orderBy('name')->limit($perGroup)->get()
                ->map(fn ($s) => [
                    'label' => $s->name,
                    'sub' => trim(($s->admission_number ? $s->admission_number.' · ' : '').($s->branch?->name ?? ''), ' ·'),
                    'url' => url('/admissions?student='.$s->id),
                ])->all();
            $this->push($groups, 'Students', 'admission', $items);
        }

        if ($user->can('enquiry.view')) {
            $items = Enquiry::query()->with('course')
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('enquiry_number', 'like', $like)->orWhere('phone', 'like', $like))
                ->latest()->limit($perGroup)->get()
                ->map(fn ($e) => [
                    'label' => $e->name,
                    'sub' => trim(($e->enquiry_number ? $e->enquiry_number.' · ' : '').($e->course?->name ?? ''), ' ·'),
                    'url' => url('/enquiries?enquiry='.$e->id),
                ])->all();
            $this->push($groups, 'Enquiries', 'enquiry', $items);
        }

        if ($user->can('fee.view')) {
            $inv = Invoice::query()->with('student')
                ->where('invoice_number', 'like', $like)
                ->latest()->limit($perGroup)->get()
                ->map(fn ($i) => [
                    'label' => $i->invoice_number,
                    'sub' => ($i->student?->name ?? '').' · '.paise_to_rupees((int) $i->balance).' due',
                    'url' => url('/fees?student='.$i->student_id),
                ])->all();
            $this->push($groups, 'Invoices', 'fees', $inv);

            $pay = Payment::query()->with('student')
                ->where('receipt_number', 'like', $like)
                ->latest()->limit($perGroup)->get()
                ->map(fn ($p) => [
                    'label' => $p->receipt_number,
                    'sub' => ($p->student?->name ?? '').' · '.paise_to_rupees((int) $p->amount),
                    'url' => route('receipts.show', $p->id),
                ])->all();
            $this->push($groups, 'Receipts', 'fees', $pay);
        }

        if ($user->can('batch.view')) {
            $items = Batch::query()->with('course')
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like))
                ->orderBy('name')->limit($perGroup)->get()
                ->map(fn ($b) => ['label' => $b->name, 'sub' => $b->course?->name ?? $b->code, 'url' => url('/batches?view='.$b->id)])->all();
            $this->push($groups, 'Batches', 'batch', $items);
        }

        if ($user->can('settings.update')) {
            $items = Staff::query()->with('primaryBranch')
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('employee_code', 'like', $like))
                ->orderBy('name')->limit($perGroup)->get()
                ->map(fn ($s) => ['label' => $s->name, 'sub' => trim(($s->designation ? $s->designation.' · ' : '').($s->email ?? ''), ' ·'), 'url' => url('/staff?view='.$s->id)])->all();
            $this->push($groups, 'Staff', 'staff', $items);
        }

        if ($user->hasAllBranchAccess()) {
            $courses = Course::query()->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like))
                ->orderBy('name')->limit($perGroup)->get()
                ->map(fn ($c) => ['label' => $c->name, 'sub' => $c->code, 'url' => url('/courses?view='.$c->id)])->all();
            $this->push($groups, 'Courses', 'course', $courses);

            $branches = Branch::query()->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('code', 'like', $like)->orWhere('city', 'like', $like))
                ->orderBy('name')->limit($perGroup)->get()
                ->map(fn ($b) => ['label' => $b->name, 'sub' => trim(($b->code ? $b->code.' · ' : '').($b->city ?? ''), ' ·'), 'url' => url('/branches?edit='.$b->id)])->all();
            $this->push($groups, 'Branches', 'branch', $branches);
        }

        return $groups;
    }

    private function push(array &$groups, string $type, string $icon, array $items): void
    {
        if (! empty($items)) {
            $groups[] = ['type' => $type, 'icon' => $icon, 'items' => $items];
        }
    }
}
