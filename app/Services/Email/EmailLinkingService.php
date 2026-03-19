<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Models\Company;
use App\Models\Email;
use App\Models\People;

final class EmailLinkingService
{
    // Public email domains to skip for company matching
    private const array SKIP_DOMAINS = [
        'gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com',
        'icloud.com', 'protonmail.com', 'live.com', 'msn.com',
    ];

    public function linkEmail(Email $email): void
    {
        $participants = $email->participants()->with('person')->get();
        $teamId = $email->team_id;

        foreach ($participants as $participant) {
            // 1. Try to match existing People record by email address
            $person = People::query()->where('team_id', $teamId)
                ->whereHas('customFieldValues', fn ($q) => $q->where('value', $participant->email_address)
                )
                ->first();

            if ($person) {
                $participant->update(['person_id' => $person->getKey()]);
                $email->people()->syncWithoutDetaching([$person->getKey()]);

                // Update communication intelligence
                $person->updateQuietly([
                    'last_email_at' => $email->sent_at,
                    'last_interaction_at' => $email->sent_at,
                ]);

                // Link to person's company if set
                if ($person->company_id) {
                    $email->companies()->syncWithoutDetaching([$person->company_id]);
                }
            }

            // 2. Try to match Company by email domain
            $domain = $this->extractDomain($participant->email_address);
            if ($domain && ! $this->isSkippedDomain($domain)) {
                $company = Company::query()->where('team_id', $teamId)
                    ->whereHas('customFieldValues', fn ($q) => $q->where('value', 'like', "%{$domain}%")
                    )
                    ->first();

                if ($company) {
                    $email->companies()->syncWithoutDetaching([$company->getKey()]);

                    $company->updateQuietly([
                        'last_email_at' => $email->sent_at,
                        'last_interaction_at' => $email->sent_at,
                    ]);
                }
            }
        }
    }

    private function extractDomain(string $email): ?string
    {
        $parts = explode('@', $email);

        return count($parts) === 2 ? $parts[1] : null;
    }

    private function isSkippedDomain(string $domain): bool
    {
        return in_array(strtolower($domain), self::SKIP_DOMAINS, true);
    }
}
