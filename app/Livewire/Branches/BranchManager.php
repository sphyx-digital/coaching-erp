<?php

namespace App\Livewire\Branches;

use App\Livewire\Concerns\WithTableTools;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BranchManager extends Component
{
    use WithTableTools;

    /** Indian states with GST state codes (subset; extend as needed). */
    public const STATES = [
        'Madhya Pradesh' => '23', 'Maharashtra' => '27', 'Delhi' => '07', 'Karnataka' => '29',
        'Tamil Nadu' => '33', 'Gujarat' => '24', 'Rajasthan' => '08', 'Uttar Pradesh' => '09',
        'West Bengal' => '19', 'Telangana' => '36', 'Kerala' => '32', 'Punjab' => '03',
        'Haryana' => '06', 'Bihar' => '10', 'Chhattisgarh' => '22',
    ];

    public const AMENITIES = ['AC classrooms', 'Library', 'Wi-Fi', 'Parking', 'Cafeteria', 'Science labs', 'Computer lab', 'Hostel', 'Transport', 'CCTV', 'Doubt-clearing zone', 'Sports'];

    public const BRANCH_TYPES = ['centre' => 'Centre', 'main' => 'Main / HQ', 'franchise' => 'Franchise'];

    public bool $showModal = false;

    public ?int $editingId = null;

    /** @var array<string,mixed> */
    public array $data = [];

    public array $publishFilter = []; // multi-select: published / draft / active / inactive

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasAllBranchAccess(), 403);
    }

    private function blank(): array
    {
        return [
            'name' => '', 'code' => '', 'branch_type' => 'centre', 'legal_name' => '', 'slug' => '',
            'established_on' => null, 'student_capacity' => null, 'is_active' => true, 'is_published' => false,
            'phone' => '', 'alt_phone' => '', 'whatsapp' => '', 'email' => '', 'support_email' => '',
            'address' => '', 'address_line2' => '', 'landmark' => '', 'locality' => '', 'city' => '',
            'state' => '', 'pincode' => '', 'country' => 'India', 'latitude' => null, 'longitude' => null, 'google_maps_url' => '',
            'manager_name' => '', 'manager_phone' => '', 'manager_email' => '',
            'gstin' => '', 'pan' => '', 'registration_number' => '',
            'tagline' => '', 'description' => '', 'about' => '', 'hero_image' => '', 'thumbnail' => '',
            'amenities' => [], 'social_facebook' => '', 'social_instagram' => '', 'social_youtube' => '', 'social_website' => '',
            'seo_title' => '', 'seo_description' => '', 'seo_keywords' => '', 'display_order' => 0,
        ];
    }

    public function openCreate(): void
    {
        abort_unless(Auth::user()?->can('settings.update') || Auth::user()?->hasAllBranchAccess(), 403);
        $this->resetValidation();
        $this->editingId = null;
        $this->data = $this->blank();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $branch = Branch::findOrFail($id);
        $this->editingId = $id;
        $this->data = array_merge($this->blank(), $branch->only(array_keys($this->blank())));
        $this->data['amenities'] = $branch->amenities ?? [];
        $social = $branch->social ?? [];
        $this->data['social_facebook'] = $social['facebook'] ?? '';
        $this->data['social_instagram'] = $social['instagram'] ?? '';
        $this->data['social_youtube'] = $social['youtube'] ?? '';
        $this->data['social_website'] = $social['website'] ?? '';
        $this->data['established_on'] = optional($branch->established_on)->toDateString();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'data.name' => ['required', 'string', 'max:255'],
            'data.code' => ['required', 'string', 'max:20'],
            'data.email' => ['nullable', 'email'],
            'data.support_email' => ['nullable', 'email'],
            'data.manager_email' => ['nullable', 'email'],
            'data.gstin' => ['nullable', 'string', 'size:15'],
            'data.pan' => ['nullable', 'string', 'size:10'],
            'data.pincode' => ['nullable', 'regex:/^[0-9]{6}$/'],
            'data.phone' => ['nullable', 'regex:/^[0-9]{6,15}$/'],
            'data.alt_phone' => ['nullable', 'regex:/^[0-9]{6,15}$/'],
            'data.whatsapp' => ['nullable', 'regex:/^[0-9]{6,15}$/'],
            'data.manager_phone' => ['nullable', 'regex:/^[0-9]{6,15}$/'],
        ], [
            'data.*.regex' => 'Enter digits only.',
        ]);

        $d = $this->data;
        $payload = collect($d)->except(['social_facebook', 'social_instagram', 'social_youtube', 'social_website'])->all();
        $payload['institute_id'] = current_institute()?->id;
        $payload['state_code'] = self::STATES[$d['state']] ?? null;
        $payload['social'] = array_filter([
            'facebook' => $d['social_facebook'], 'instagram' => $d['social_instagram'],
            'youtube' => $d['social_youtube'], 'website' => $d['social_website'],
        ]);
        $payload['amenities'] = array_values($d['amenities'] ?? []);

        if ($this->editingId) {
            Branch::findOrFail($this->editingId)->update($payload);
        } else {
            Branch::create($payload);
        }

        $this->showModal = false;
        session()->flash('ok', 'Branch saved.');
    }

    public function toggleActive(int $id): void
    {
        $b = Branch::findOrFail($id);
        $b->update(['is_active' => ! $b->is_active]);
    }

    public function togglePublishFilter(string $key): void
    {
        in_array($key, $this->publishFilter, true)
            ? $this->publishFilter = array_values(array_diff($this->publishFilter, [$key]))
            : $this->publishFilter[] = $key;
    }

    public function render()
    {
        $q = Branch::query();
        if ($this->search !== '') {
            $s = '%'.$this->search.'%';
            $q->where(fn ($w) => $w->where('name', 'like', $s)->orWhere('code', 'like', $s)->orWhere('city', 'like', $s));
        }
        foreach ($this->publishFilter as $f) {
            match ($f) {
                'published' => $q->where('is_published', true),
                'draft' => $q->where('is_published', false),
                'active' => $q->where('is_active', true),
                'inactive' => $q->where('is_active', false),
                default => null,
            };
        }
        $q = $this->sortField ? $this->applySort($q, 'name') : $q->orderBy('display_order')->orderBy('name');

        return view('livewire.branches.branch-manager', [
            'branches' => $q->get(),
            'states' => array_combine(array_keys(self::STATES), array_keys(self::STATES)),
            'branchTypes' => self::BRANCH_TYPES,
            'amenityOptions' => self::AMENITIES,
        ]);
    }
}
