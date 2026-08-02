<?php

namespace App\Http\Controllers;

use App\Services\Import\ImportService;
use Illuminate\Support\Facades\Auth;

class ImportTemplateController extends Controller
{
    public function students(ImportService $service)
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);

        $headers = ImportService::STUDENT_COLUMNS;
        $sample = ['Riya Sharma', '9990001111', 'riya@example.com', 'Papa Sharma', '9990002222', '5000'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'students-import-template.csv', ['Content-Type' => 'text/csv']);
    }
}
