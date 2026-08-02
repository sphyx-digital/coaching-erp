<?php

namespace App\Livewire\Import;

use App\Models\ImportBatch;
use App\Services\Import\ImportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ImportManager extends Component
{
    public string $label = '';

    public string $csv = '';

    public array $rows = [];

    public array $preview = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
    }

    public function analyse(ImportService $service): void
    {
        $this->rows = $this->parse();
        $this->preview = $service->preview($this->rows);
    }

    public function commit(ImportService $service): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        $this->rows = $this->parse();

        try {
            $branchId = Auth::user()->staff?->branch_id ?? current_institute()?->branches()->value('id');
            $batch = $service->commitStudents($this->rows, $this->label ?: 'Import '.now()->format('d-m-Y H:i'), current_institute()?->id, $branchId);
            session()->flash('ok', "Imported {$batch->imported_count} students.");
            $this->reset(['csv', 'rows', 'preview', 'label']);
        } catch (\DomainException $e) {
            $this->addError('import', $e->getMessage());
        }
    }

    public function rollback(int $id, ImportService $service): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
        $service->rollback(ImportBatch::findOrFail($id));
        session()->flash('ok', 'Batch rolled back.');
    }

    private function parse(): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($this->csv));
        if (count($lines) < 2) {
            return [];
        }
        $header = array_map('trim', str_getcsv(array_shift($lines)));
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = str_getcsv($line);
            $cells = array_pad($cells, count($header), '');
            $rows[] = array_combine($header, array_slice($cells, 0, count($header)));
        }

        return $rows;
    }

    public function render()
    {
        return view('livewire.import.import-manager', [
            'batches' => ImportBatch::latest()->limit(20)->get(),
        ]);
    }
}
