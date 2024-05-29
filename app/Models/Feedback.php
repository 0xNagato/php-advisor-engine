<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperFeedback
 */
class Feedback extends Model
{
    protected $fillable = [
        'user_id',
        'message',
        'exception_message',
        'exception_trace',
    ];
}
