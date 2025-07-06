<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    use HasFactory;

    protected $table = 'sms_messages';

    protected $fillable = [
        'message',
        'scheduled_at',
        'scheduled_at_utc',
        'status',
        'type',
        'recipient_data',
        'regions',
        'recipient_types',
        'created_by',
        'total_recipients',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'recipient_data' => 'array',
            'regions' => 'array',
            'recipient_types' => 'array',
            'scheduled_at' => 'datetime',
            'scheduled_at_utc' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
