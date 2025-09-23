<?php

namespace App\Console\Commands;

use DateTime;
use Illuminate\Console\Command;

class ParseTwilioSmsLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:parse-twilio-sms-logs {file? : Path to the SMS log CSV file} {--debug : Show sample phone numbers for unknown country codes} {--error-code= : Filter by specific error code (e.g., 21408)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parse Twilio SMS logs and analyze error patterns by country';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file') ?? $this->findLatestLogFile();

        if (! $filePath || ! file_exists($filePath)) {
            $this->error('SMS log file not found. Please provide a valid file path.');

            return 1;
        }

        $errorCodeFilter = $this->option('error-code');

        $this->info("Parsing SMS log file: {$filePath}");
        if ($errorCodeFilter) {
            $this->info("Filtering by error code: {$errorCodeFilter}");
        }

        $errors = $this->parseLogFile($filePath, $errorCodeFilter);
        $this->displayResults($errors, $errorCodeFilter);

        return 0;
    }

    private function findLatestLogFile(): ?string
    {
        $logDir = storage_path('app');
        $files = glob($logDir.'/sms-log-*.csv');

        if (empty($files)) {
            return null;
        }

        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        return $files[0];
    }

    private function parseLogFile(string $filePath, ?string $errorCodeFilter = null): array
    {
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);

        // Map header columns for easy access
        $columns = array_flip($header);

        $errors = [];
        $unknownSamples = []; // Store sample phone numbers for unknown codes
        $totalRows = 0;
        $errorRows = 0;
        $startDate = null;
        $endDate = null;

        while (($row = fgetcsv($handle)) !== false) {
            $totalRows++;

            if (empty($row) || count($row) < count($header)) {
                continue;
            }

            $status = $row[$columns['Status']] ?? '';

            // Only process failed and undelivered messages
            if (! in_array($status, ['failed', 'undelivered'])) {
                continue;
            }

            $errorRows++;
            $toNumber = $row[$columns['To']] ?? '';
            $errorCode = $row[$columns['ErrorCode']] ?? '';
            $sentDate = $row[$columns['SentDate']] ?? '';

            // Track date range for all error messages
            if ($sentDate) {
                $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:sP', $sentDate);
                if ($dateTime) {
                    if ($startDate === null || $dateTime < $startDate) {
                        $startDate = $dateTime;
                    }
                    if ($endDate === null || $dateTime > $endDate) {
                        $endDate = $dateTime;
                    }
                }
            }

            // Apply error code filter if specified
            if ($errorCodeFilter && $errorCode !== $errorCodeFilter) {
                continue;
            }

            $country = $this->extractCountryFromPhoneNumber($toNumber);
            $key = $country.' + Error '.$errorCode;

            // Store sample phone numbers for unknown countries
            if (str_starts_with($country, 'Unknown') && ! isset($unknownSamples[$country])) {
                $unknownSamples[$country] = $toNumber;
            }

            if (! isset($errors[$key])) {
                $errors[$key] = 0;
            }

            $errors[$key]++;
        }

        fclose($handle);

        $this->info("Processed {$totalRows} total rows, found {$errorRows} error rows");

        // Show date range
        if ($startDate && $endDate) {
            $this->info("Date range: {$startDate->format('M j, Y H:i')} to {$endDate->format('M j, Y H:i')}");
        }

        // Show unknown phone number samples in debug mode
        if ($this->option('debug') && ! empty($unknownSamples)) {
            $this->line('');
            $this->info('Sample phone numbers for unknown country codes:');
            foreach ($unknownSamples as $country => $sampleNumber) {
                $this->line("  {$country}: {$sampleNumber}");
            }
            $this->line('');
        }

        // Sort by count descending
        arsort($errors);

        return $errors;
    }

    private function extractCountryFromPhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Map common country codes to country names with ISO codes
        $countryCodes = [
            '1' => 'US/CA (United States/Canada)',
            '34' => 'ES (Spain)',
            '966' => 'SA (Saudi Arabia)',
            '972' => 'IL (Israel)',
            '971' => 'AE (UAE)',
            '49' => 'DE (Germany)',
            '33' => 'FR (France)',
            '44' => 'GB (United Kingdom)',
            '39' => 'IT (Italy)',
            '52' => 'MX (Mexico)',
            '351' => 'PT (Portugal)',
            '32' => 'BE (Belgium)',
            '31' => 'NL (Netherlands)',
            '47' => 'NO (Norway)',
            '46' => 'SE (Sweden)',
            '45' => 'DK (Denmark)',
            '41' => 'CH (Switzerland)',
            '43' => 'AT (Austria)',
            '55' => 'BR (Brazil)',
            '54' => 'AR (Argentina)',
            '56' => 'CL (Chile)',
            '57' => 'CO (Colombia)',
            '58' => 'VE (Venezuela)',
            // Additional country codes from unknown analysis
            '212' => 'MA (Morocco)',
            '965' => 'KW (Kuwait)',
            '595' => 'PY (Paraguay)',
            '30' => 'GR (Greece)',
            '62' => 'ID (Indonesia)',
            '7' => 'RU (Russia)',
            '48' => 'PL (Poland)',
            '20' => 'EG (Egypt)',
            '389' => 'MK (North Macedonia)',
            '974' => 'QA (Qatar)',
            '51' => 'PE (Peru)',
            '61' => 'AU (Australia)',
            '352' => 'LU (Luxembourg)',
            '262' => 'RE (Réunion)',
            '973' => 'BH (Bahrain)',
            '216' => 'TN (Tunisia)',
            '92' => 'PK (Pakistan)',
            '357' => 'CY (Cyprus)',
            '94' => 'LK (Sri Lanka)',
            '233' => 'GH (Ghana)',
        ];

        foreach ($countryCodes as $code => $country) {
            if (str_starts_with((string) $phoneNumber, $code)) {
                return $country;
            }
        }

        // Extract first 1-3 digits as unknown country code
        $unknownCode = substr((string) $phoneNumber, 0, 3);
        if (strlen($unknownCode) > 1) {
            $unknownCode = substr((string) $phoneNumber, 0, 2);
        }
        if (strlen($unknownCode) > 1) {
            $unknownCode = substr((string) $phoneNumber, 0, 1);
        }

        return "Unknown (+{$unknownCode})";
    }

    private function getTwilioErrorDescription(string $errorCode): string
    {
        $errorDescriptions = [
            '21408' => 'Permission to send an SMS has not been enabled for the region',
            '21614' => 'Mobile number is from a country/region where SMS is not currently supported',
            '21635' => 'Invalid destination phone number',
            '30003' => 'Unreachable destination handset',
            '30005' => 'Unknown destination handset',
            '30008' => 'Message delivery - unknown error',
            '30034' => 'Message blocked - considered spam',
            '30612' => 'Message cannot be sent to landline',
            '21612' => 'The \'To\' phone number is not currently reachable via SMS',
            '21610' => 'Message cannot be sent to the \'To\' number because the customer has replied STOP',
            '21211' => 'Invalid \'To\' phone number',
            '21601' => 'Phone number is unverified',
            '21602' => 'Message body is required',
            '21603' => 'The message cannot be sent because the destination phone number is not SMS-capable',
            '21604' => 'The destination phone number is too short',
            '21605' => 'The destination phone number is too long',
            '21606' => 'This phone number does not appear to be valid',
            '21611' => 'This phone number is not currently supported',
        ];

        return $errorDescriptions[$errorCode] ?? 'Unknown error';
    }

    private function displayResults(array $errors, ?string $errorCodeFilter = null): void
    {
        if (empty($errors)) {
            $this->warn('No SMS errors found in the log file.');

            return;
        }

        if ($errorCodeFilter) {
            $errorDescription = $this->getTwilioErrorDescription($errorCodeFilter);
            $this->info("Countries affected by error code {$errorCodeFilter}: {$errorDescription}");
        } else {
            $this->info('Top Country + Error Code combinations:');
        }
        $this->line('');

        $headers = ['Country', 'Error Code', 'Description', 'Count'];
        $rows = [];

        foreach ($errors as $combination => $count) {
            preg_match('/^(.+) \+ Error (.+)$/', $combination, $matches);
            $country = $matches[1] ?? 'Unknown';
            $errorCode = $matches[2] ?? 'Unknown';
            $description = $this->getTwilioErrorDescription($errorCode);

            $rows[] = [$country, $errorCode, $description, $count];
        }

        $this->table($headers, $rows);

        $this->line('');
        $this->info('Total unique error combinations: '.count($errors));
        $this->info('Total error occurrences: '.array_sum($errors));
    }
}
