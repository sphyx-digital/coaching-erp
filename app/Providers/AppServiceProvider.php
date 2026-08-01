<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Schema conventions shared by every migration (Phase 1).

        // Audit columns on every table: who created / last updated the row.
        // Nullable so system/seed writes and pre-auth rows are valid.
        Blueprint::macro('auditColumns', function (): void {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('created_by')->nullable()->index();
            $this->unsignedBigInteger('updated_by')->nullable();
        });

        // Money is stored in integer paise (never floats). bigInteger holds
        // amounts well beyond any institute's turnover without precision loss.
        Blueprint::macro('paise', function (string $column, int $default = 0) {
            /** @var Blueprint $this */
            return $this->bigInteger($column)->default($default);
        });
    }
}
