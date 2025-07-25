<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

class UserCode extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'code'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a two-factor code and update or create the user code record.
     *
     * @return int The generated code
     *
     * @throws Throwable
     */
    public static function generateCodeForUser(User $user): int
    {
        $code = random_int(100000, 999999);

        self::query()->updateOrCreate(['user_id' => $user->id], ['code' => $code]);

        return $code;
    }
}
