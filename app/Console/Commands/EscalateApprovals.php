<?php

namespace App\Console\Commands;

use App\Services\Approvals\ApprovalService;
use Illuminate\Console\Command;

class EscalateApprovals extends Command
{
    protected $signature = 'approvals:escalate';

    protected $description = 'Escalate approval requests past their SLA to the next role';

    public function handle(ApprovalService $service): int
    {
        $count = $service->escalateOverdue();
        $this->info("Escalated {$count} overdue approval(s).");

        return self::SUCCESS;
    }
}
