<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Emails\EmailAccountStatus;
use App\Enums\Emails\EmailProvider;
use App\Models\Concerns\HasTeam;
use App\Observers\EmailAccountObserver;
use Database\Factories\EmailAccountFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property EmailProvider $provider
 * @property string $email_address
 * @property string|null $display_name
 * @property string $access_token
 * @property string|null $refresh_token
 * @property Carbon|null $token_expires_at
 */
#[ObservedBy(EmailAccountObserver::class)]
final class EmailAccount extends Model
{
    // TODO::Check the implementation of the HasCreator trait

    /** @use HasFactory<EmailAccountFactory> **/
    use HasFactory;

    use HasTeam;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'user_id',
        'provider',
        'email_address',
        'display_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'sync_cursor',
        'last_synced_at',
        'status',
        'last_error',
        'sync_inbox',
        'sync_sent',
    ];

    protected $casts = [
        'provider' => EmailProvider::class,
        'status' => EmailAccountStatus::class,
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'sync_inbox' => 'boolean',
        'sync_sent' => 'boolean',
        // Encrypts tokens at the model level — never stored in plaintext
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];

    // Relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Email, $this>
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    // Helpers
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at !== null && $this->token_expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === EmailAccountStatus::ACTIVE && ! $this->isTokenExpired();
    }

    /**
     * @return Attribute<string, string>
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->provider->getLabel() } - $this->email_address",
        );
    }
}
