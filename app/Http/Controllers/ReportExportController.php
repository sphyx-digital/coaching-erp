<?php

namespace App\Http\Controllers;

use App\Services\Audit\AuditLogger;
use App\Services\Reports\ReportService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function export(string $report, ReportService $reports, AuditLogger $audit): StreamedResponse
    {
        abort_unless(Auth::user()?->can('report.view'), 403);

        [$title, $head, $rows, $key] = match ($report) {
            'collections' => [
                'Collections by mode',
                ['Mode', 'Amount (INR)'],
                collect($reports->collectionsByMode())->map(fn ($v, $k) => [ucfirst($k), number_format($v / 100, 2)])->values()->all(),
                ['INR = Indian Rupees (amounts exclude paise rounding shown to 2 dp)'],
            ],
            'outstanding' => [
                'Outstanding by ageing',
                ['Ageing (days)', 'Balance (INR)'],
                collect($reports->outstandingAgeing())->map(fn ($v, $k) => [$k, number_format($v / 100, 2)])->values()->all(),
                ['Ageing = days since invoice date', 'INR = Indian Rupees'],
            ],
            'funnel' => [
                'Enquiry funnel',
                ['Status', 'Count'],
                collect($reports->enquiryFunnel())->map(fn ($v, $k) => [$k, $v])->values()->all(),
                ['Funnel = enquiry pipeline stage counts'],
            ],
            default => abort(404),
        };

        $audit->log('report.exported', after: ['report' => $report]);

        $filename = 'report-'.$report.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($title, $head, $rows, $key) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [$title]);
            fputcsv($out, ['Generated', now()->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['Abbreviation key']);
            foreach ($key as $line) {
                fputcsv($out, [$line]);
            }
            fputcsv($out, []);
            fputcsv($out, $head);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
