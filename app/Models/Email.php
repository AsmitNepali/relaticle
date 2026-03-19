<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreationSource;
use App\Enums\Emails\EmailDirection;
use App\Enums\Emails\EmailPrivacyTier;
use App\Models\Concerns\HasTeam;
use App\Observers\EmailObserver;
use Carbon\Carbon;
use Database\Factories\EmailFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $message_id
 * @property string|null $thread_id
 * @property string $subject
 * @property string|null $body_text
 * @property string|null $body_html
 * @property Carbon|null $sent_at
 * @property EmailDirection $direction
 * @property EmailPrivacyTier $privacy_tier
 * @property bool $shared_with_team
 * @property CreationSource $creation_source
 */
#[ObservedBy(EmailObserver::class)]
final class Email extends Model
{
    /** @use HasFactory<EmailFactory> */
    use HasFactory;

    use HasTeam;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'email_account_id',
        'message_id',
        'thread_id',
        'subject',
        'body_text',
        'body_html',
        'sent_at',
        'direction',
        'privacy_tier',
        'shared_with_team',
        'creation_source',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'direction' => EmailDirection::class,
        'privacy_tier' => EmailPrivacyTier::class,
        'creation_source' => CreationSource::class,
        'shared_with_team' => 'boolean',
    ];

    protected $attributes = [
        'privacy_tier' => EmailPrivacyTier::METADATA_ONLY,
        'shared_with_team' => false,
        'creation_source' => CreationSource::SYSTEM,
    ];

    // Relationships

    /**
     * @return BelongsTo<EmailAccount, $this>
     */
    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    /**
     * @return HasMany<EmailParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(EmailParticipant::class);
    }

    /**
     * @return HasMany<EmailParticipant, $this>
     */
    public function from(): HasMany
    {
        return $this->hasMany(EmailParticipant::class)->where('role', 'from');
    }

    /**
     * @return MorphToMany<People, $this>
     */
    public function people(): MorphToMany
    {
        return $this->morphedByMany(People::class, 'emailable');
    }

    /**
     * @return MorphToMany<Company, $this>
     */
    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, 'emailable');
    }

    /**
     * @return MorphToMany<Opportunity, $this>
     */
    public function opportunities(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, 'emailable');
    }
}
