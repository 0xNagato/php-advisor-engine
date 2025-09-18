<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @mixin IdeHelperToken
 */
class Token extends PersonalAccessToken
{
    use HasFactory;

    protected $table = 'personal_access_tokens';
}
