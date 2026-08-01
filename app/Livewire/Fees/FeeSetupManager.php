<?php

namespace App\Livewire\Fees;

use App\Models\Course;
use App\Models\FeeComponent;
use App\Models\FeePlan;
use App\Models\TaxRate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FeeSetupManager extends Component
{
    // Tax rate
    public string $taxName = '';

    public ?float $taxPercent = null;

    // Fee plan
    public string $planName = '';

    public ?int $planCourseId = null;

    // Component
    public ?int $componentPlanId = null;

    public string $componentName = '';

    public ?float $componentAmount = null;   // rupees

    public bool $componentTaxable = true;

    public ?int $componentTaxRateId = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('fee.view'), 403);
    }

    public function addTaxRate(): void
    {
        abort_unless(Auth::user()?->can('fee.create'), 403);
        $data = $this->validate([
            'taxName' => ['required', 'string', 'max:255'],
            'taxPercent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        TaxRate::create([
            'institute_id' => current_institute()?->id,
            'name' => $data['taxName'],
            'rate_bp' => (int) round($data['taxPercent'] * 100), // 18% -> 1800
        ]);
        $this->reset(['taxName', 'taxPercent']);
    }

    public function addPlan(): void
    {
        abort_unless(Auth::user()?->can('fee.create'), 403);
        $data = $this->validate([
            'planName' => ['required', 'string', 'max:255'],
            'planCourseId' => ['nullable', 'exists:courses,id'],
        ]);

        FeePlan::create([
            'institute_id' => current_institute()?->id,
            'course_id' => $data['planCourseId'],
            'name' => $data['planName'],
        ]);
        $this->reset(['planName', 'planCourseId']);
    }

    public function addComponent(): void
    {
        abort_unless(Auth::user()?->can('fee.create'), 403);
        $data = $this->validate([
            'componentPlanId' => ['required', 'exists:fee_plans,id'],
            'componentName' => ['required', 'string', 'max:255'],
            'componentAmount' => ['required', 'numeric', 'min:0'],
            'componentTaxRateId' => ['nullable', 'exists:tax_rates,id'],
        ]);

        FeeComponent::create([
            'fee_plan_id' => $data['componentPlanId'],
            'tax_rate_id' => $this->componentTaxable ? $data['componentTaxRateId'] : null,
            'name' => $data['componentName'],
            'is_taxable' => $this->componentTaxable,
            'amount' => (int) round($data['componentAmount'] * 100), // rupees -> paise
        ]);
        $this->reset(['componentName', 'componentAmount', 'componentTaxRateId']);
        $this->componentTaxable = true;
    }

    public function render()
    {
        return view('livewire.fees.fee-setup-manager', [
            'taxRates' => TaxRate::orderBy('name')->get(),
            'plans' => FeePlan::with(['course', 'components.taxRate'])->orderBy('name')->get(),
            'courses' => Course::orderBy('name')->pluck('name', 'id'),
            'taxOptions' => TaxRate::orderBy('name')->get()->mapWithKeys(fn ($t) => [$t->id => $t->name])->toArray(),
            'planOptions' => FeePlan::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
