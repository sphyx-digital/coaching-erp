<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-client key/value override that takes precedence over config/client.php.
 * Read through App\Support\ClientSettings (wired in Phase 3).
 */
class ClientSetting extends Model
{
    protected $guarded = ['id'];
}
