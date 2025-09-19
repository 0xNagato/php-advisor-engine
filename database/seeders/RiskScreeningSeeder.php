<?php

namespace Database\Seeders;

use App\Models\RiskBlacklist;
use App\Models\RiskWhitelist;
use Illuminate\Database\Seeder;

class RiskScreeningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed common disposable email domains for blacklist
        $disposableDomains = [
            'mailinator.com',
            'guerrillamail.com',
            '10minutemail.com',
            'tempmail.com',
            'throwaway.email',
            'yopmail.com',
            'maildrop.cc',
            'mintemail.com',
            'temp-mail.org',
            'fakeinbox.com',
            'sharklasers.com',
            'guerrillamail.info',
            'spam4.me',
            'grr.la',
            'mailnesia.com',
        ];

        foreach ($disposableDomains as $domain) {
            RiskBlacklist::firstOrCreate(
                [
                    'type' => RiskBlacklist::TYPE_DOMAIN,
                    'value' => $domain,
                ],
                [
                    'reason' => 'Known disposable email domain',
                    'created_by' => 1, // System user
                ]
            );
        }

        // Seed known fake/test phone numbers for blacklist
        $testPhones = [
            '5555555555',
            '1234567890',
            '9876543210',
            '1111111111',
            '0000000000',
            '9999999999',
            '+19898989898',
            '+11231231234',
        ];

        foreach ($testPhones as $phone) {
            RiskBlacklist::firstOrCreate(
                [
                    'type' => RiskBlacklist::TYPE_PHONE,
                    'value' => $phone,
                ],
                [
                    'reason' => 'Known test/fake phone number',
                    'created_by' => 1,
                ]
            );
        }

        // Seed test names for blacklist
        $testNames = [
            'test test',
            'test user',
            'demo user',
            'fake name',
            'asdf asdf',
            'qwerty qwerty',
        ];

        foreach ($testNames as $name) {
            RiskBlacklist::firstOrCreate(
                [
                    'type' => RiskBlacklist::TYPE_NAME,
                    'value' => $name,
                ],
                [
                    'reason' => 'Known test/fake name',
                    'created_by' => 1,
                ]
            );
        }

        // Seed some VPN/datacenter IP ranges for blacklist (CIDR notation)
        $vpnRanges = [
            '104.16.0.0/12',    // Cloudflare
            '162.158.0.0/15',   // Cloudflare
            '198.41.128.0/17',  // Cloudflare
            '192.42.116.16',    // Tor exit node
            '199.87.154.255',   // Tor exit node
        ];

        foreach ($vpnRanges as $range) {
            RiskBlacklist::firstOrCreate(
                [
                    'type' => RiskBlacklist::TYPE_IP,
                    'value' => $range,
                ],
                [
                    'reason' => 'VPN/Datacenter IP range',
                    'created_by' => 1,
                ]
            );
        }

        // Seed some trusted domains for whitelist
        $trustedDomains = [
            'marriott.com',
            'hilton.com',
            'hyatt.com',
            'fourseasons.com',
            'ritzcarlton.com',
            'mandarinoriental.com',
        ];

        foreach ($trustedDomains as $domain) {
            RiskWhitelist::firstOrCreate(
                [
                    'type' => RiskWhitelist::TYPE_DOMAIN,
                    'value' => $domain,
                ],
                [
                    'notes' => 'Trusted hotel partner domain',
                    'created_by' => 1,
                ]
            );
        }

        $this->command->info('Risk screening seed data created successfully.');
    }
}