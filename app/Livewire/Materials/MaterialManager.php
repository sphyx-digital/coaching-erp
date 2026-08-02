<?php

namespace App\Livewire\Materials;

use App\Livewire\Concerns\WithBulkSelect;
use App\Models\Batch;
use App\Models\Course;
use App\Models\StudyMaterial;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MaterialManager extends Component
{
    use WithBulkSelect;

    public const TYPES = ['document' => 'Document', 'video' => 'Video', 'note' => 'Note', 'link' => 'Link'];

    public string $courseFilter = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    /** @var array<string,mixed> */
    public array $data = [];

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('assessment.view') || Auth::user()?->hasAllBranchAccess(), 403);
    }

    private function canManage(): bool
    {
        return Auth::user()?->can('assessment.create') || Auth::user()?->hasAllBranchAccess();
    }

    private function blank(): array
    {
        return [
            'title' => '', 'description' => '', 'type' => 'document', 'url' => '',
            'course_id' => '', 'batch_id' => '', 'subject_id' => '', 'is_published' => true,
        ];
    }

    public function openCreate(): void
    {
        abort_unless($this->canManage(), 403);
        $this->resetValidation();
        $this->editingId = null;
        $this->data = $this->blank();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $m = StudyMaterial::findOrFail($id);
        $this->editingId = $id;
        $this->data = [
            'title' => $m->title, 'description' => $m->description ?? '', 'type' => $m->type, 'url' => $m->url,
            'course_id' => $m->course_id ?: '', 'batch_id' => $m->batch_id ?: '', 'subject_id' => $m->subject_id ?: '',
            'is_published' => (bool) $m->is_published,
        ];
        $this->showModal = true;
    }

    public function save(): void
    {
        abort_unless($this->canManage(), 403);
        $this->validate([
            'data.title' => ['required', 'string', 'max:180'],
            'data.type' => ['required', 'in:document,video,note,link'],
            'data.url' => ['required', 'url', 'max:1000'],
        ]);

        $payload = [
            'institute_id' => current_institute()?->id,
            'academic_session_id' => active_session()?->id,
            'title' => $this->data['title'],
            'description' => $this->data['description'] ?: null,
            'type' => $this->data['type'],
            'url' => $this->data['url'],
            'course_id' => $this->data['course_id'] ?: null,
            'batch_id' => $this->data['batch_id'] ?: null,
            'subject_id' => $this->data['subject_id'] ?: null,
            'is_published' => (bool) $this->data['is_published'],
            'published_at' => $this->data['is_published'] ? now() : null,
        ];

        if ($this->editingId) {
            StudyMaterial::findOrFail($this->editingId)->update($payload);
        } else {
            StudyMaterial::create($payload);
        }

        $this->showModal = false;
        session()->flash('material_saved', true);
    }

    public function togglePublish(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $m = StudyMaterial::findOrFail($id);
        $m->is_published = ! $m->is_published;
        $m->published_at = $m->is_published ? now() : null;
        $m->save();
    }

    public function delete(int $id): void
    {
        abort_unless($this->canManage(), 403);
        StudyMaterial::findOrFail($id)->delete();
    }

    public function bulkPublish(bool $published): void
    {
        abort_unless($this->canManage(), 403);
        StudyMaterial::whereIn('id', $this->selectedIds())->update([
            'is_published' => $published,
            'published_at' => $published ? now() : null,
        ]);
        $this->afterBulk($published ? 'published' : 'unpublished');
    }

    public function bulkDelete(): void
    {
        abort_unless($this->canManage(), 403);
        StudyMaterial::whereIn('id', $this->selectedIds())->delete();
        $this->afterBulk('deleted');
    }

    private function afterBulk(string $verb): void
    {
        $count = $this->selectedCount();
        $this->clearSelection();
        session()->flash('material_saved', ucfirst($verb)." {$count} materials.");
    }

    public function render()
    {
        $materials = StudyMaterial::with(['course', 'batch', 'subject'])
            ->when($this->courseFilter !== '', fn ($q) => $q->where('course_id', $this->courseFilter))
            ->latest()->get();
        $this->pageIds = $materials->pluck('id')->all();

        return view('livewire.materials.material-manager', [
            'materials' => $materials,
            'courses' => Course::orderBy('name')->get(),
            'batches' => Batch::orderByDesc('id')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }
}
