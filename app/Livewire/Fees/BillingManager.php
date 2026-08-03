<?php

namespace App\Livewire\Fees;

use App\Models\FeePlan;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Services\Exceptions\ExceptionService;
use App\Services\Fees\DiscountService;
use App\Services\Fees\FeeService;
use App\Services\Fees\PaymentService;
use App\Services\Fees\RefundService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BillingManager extends Component
{
    public ?int $studentId = null;

    // raise invoice
    public ?int $planId = null;

    public bool $interstate = false;

    // payment
    public ?int $payInvoiceId = null;

    public ?float $payAmount = null;

    public string $payMode = 'cash';

    public string $payReference = '';

    // discount
    public ?int $discInvoiceId = null;

    public ?float $discAmount = null;

    // refund
    public ?int $refundPaymentId = null;

    public ?float $refundAmount = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('fee.view'), 403);
        if (($sid = (int) request()->integer('student')) && Student::whereKey($sid)->exists()) {
            $this->studentId = $sid;
        }
    }

    public function raiseInvoice(FeeService $fees): void
    {
        abort_unless(Auth::user()?->can('fee.create'), 403);
        $student = Student::findOrFail($this->studentId);
        $enrollment = $student->enrollments()->latest()->first();
        $plan = FeePlan::with('components.taxRate')->findOrFail($this->planId);

        $fees->createInvoice(
            $student, $enrollment,
            $plan->components->map(fn ($c) => [
                'description' => $c->name,
                'taxable_value' => (int) $c->amount,
                'rate_bp' => $c->is_taxable && $c->taxRate ? (int) $c->taxRate->rate_bp : 0,
                'fee_component_id' => $c->id,
            ])->all(),
            $this->interstate,
        );
        session()->flash('ok', 'Invoice raised.');
    }

    public function pay(PaymentService $payments): void
    {
        abort_unless(Auth::user()?->can('fee.create'), 403);
        $invoice = Invoice::findOrFail($this->payInvoiceId);
        $paise = (int) round((float) $this->payAmount * 100);

        try {
            $payment = $payments->record(
                $invoice->student, $paise, $this->payMode, $this->payReference ?: null,
                now()->toDateString(), [$invoice->id => min($paise, (int) $invoice->balance)],
            );
            $this->reset(['payInvoiceId', 'payAmount', 'payReference']);
            session()->flash('ok', "Receipt {$payment->receipt_number} issued.");
        } catch (\DomainException $e) {
            $this->addError('pay', $e->getMessage());
        }
    }

    public function applyDiscount(DiscountService $discounts): void
    {
        abort_unless(Auth::user()?->can('fee.update'), 403);
        $invoice = Invoice::findOrFail($this->discInvoiceId);

        // Routes through the approval engine when above the threshold.
        $approval = $discounts->propose($invoice, 'fixed', (int) round((float) $this->discAmount * 100), 'Manual discount', Auth::user()->staff?->id);

        $this->reset(['discInvoiceId', 'discAmount']);
        session()->flash('ok', $approval ? 'Discount sent for approval.' : 'Discount applied.');
    }

    public function refund(RefundService $refunds): void
    {
        abort_unless(Auth::user()?->can('fee.approve'), 403);
        $payment = Payment::findOrFail($this->refundPaymentId);
        try {
            $refunds->refund($payment, (int) round((float) $this->refundAmount * 100), 'Refund', now()->toDateString());
            $this->reset(['refundPaymentId', 'refundAmount']);
            session()->flash('ok', 'Refund processed.');
        } catch (\DomainException $e) {
            $this->addError('refund', $e->getMessage());
        }
    }

    public function reversePayment(int $id, ExceptionService $ex): void
    {
        abort_unless(Auth::user()?->can('fee.approve'), 403);
        try {
            $ex->reversePayment(Payment::findOrFail($id));
            session()->flash('ok', 'Payment reversed.');
        } catch (\DomainException $e) {
            $this->addError('refund', $e->getMessage());
        }
    }

    public function cancelInvoice(int $id, ExceptionService $ex): void
    {
        abort_unless(Auth::user()?->can('fee.approve'), 403);
        try {
            $ex->cancelInvoice(Invoice::findOrFail($id));
            session()->flash('ok', 'Invoice cancelled.');
        } catch (\DomainException $e) {
            $this->addError('pay', $e->getMessage());
        }
    }

    public function render()
    {
        $student = $this->studentId ? Student::with(['enrollments'])->find($this->studentId) : null;

        return view('livewire.fees.billing-manager', [
            'students' => Student::orderBy('name')->pluck('name', 'id'),
            'plans' => FeePlan::orderBy('name')->pluck('name', 'id'),
            'student' => $student,
            'invoices' => $student ? Invoice::where('student_id', $student->id)->latest()->get() : collect(),
            'payments' => $student ? Payment::where('student_id', $student->id)->latest()->get() : collect(),
            'kpiOutstanding' => (int) Invoice::whereNotIn('status', ['paid', 'cancelled'])->sum('balance'),
            'kpiToday' => (int) Payment::whereDate('payment_date', now()->toDateString())->sum('amount'),
            'topOutstanding' => Invoice::with('student')->where('balance', '>', 0)->orderByDesc('balance')->limit(10)->get(),
        ]);
    }
}
