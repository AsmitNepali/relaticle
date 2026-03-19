<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Emails\EmailParticipantRole;
use Database\Factories\EmailParticipantFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EmailParticipant extends Model
{
    /** @use HasFactory<EmailParticipantFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'email_id',
        'email_address',
        'name',
        'role',
        'person_id',
    ];

    protected $casts = [
        'role' => EmailParticipantRole::class,
    ];

    // Relationships

    /**
     * @return BelongsTo<Email, $this>
     */
    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    /**
     * @return BelongsTo<People, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(People::class);
    }
}
