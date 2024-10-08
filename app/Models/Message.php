<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'announcement_id',
        'user_id',
        'read_at',
    ];

    /**
     * @return BelongsTo<Announcement, Message>
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    /**
     * @return BelongsTo<User, Message>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
