<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperSmsTemplate
 */
class SmsTemplate extends Model
{
    protected $fillable = ['key', 'content'];
}
