<?php

namespace App\Jobs;

use App\Http\Integrations\SimpleTexting\SimpleTexting;
use App\Models\User;
use App\Notifications\Admin\SimpleTextingDownNotification;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Sentry\State\Scope as SentryScope;

use function Sentry\captureException;
use function Sentry\captureMessage;
use function Sentry\withScope;

class SendSimpleTextingSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 300; // 5 minutes

    public function __construct(
        private readonly string $phone,
        private readonly string $text
    ) {}

    private function notifyAdminsOnFinalAttempt(string $error): void
    {
        if ($this->attempts() === $this->tries) {
            User::role('super_admin')->each(function ($admin) use ($error) {
                $admin->notify(new SimpleTextingDownNotification(
                    error: $error,
                    recipientPhone: $this->phone,
                    messageText: $this->text
                ));
            });
        }
    }

    public function handle(): void
    {
        try {
            $response = app(SimpleTexting::class)->sms(
                phone: $this->phone,
                text: $this->text
            );

            if ($response->failed()) {
                $body = $response->json();
                $status = $response->status();

                Log::info('SimpleTexting Failed - API response', [
                    'phone' => $this->phone,
                    'status' => $status,
                    'body' => $body,
                    'attempt' => $this->attempts(),
                ]);

                withScope(function (SentryScope $scope) use ($status, $body): void {
                    $scope->setContext('api_response', [
                        'status' => $status,
                        'body' => $body,
                        'attempt' => $this->attempts(),
                    ]);
                    $scope->setTag('phone', $this->phone);
                    captureMessage('SimpleTexting API Failed');
                });

                $this->fail($response->body());
            }
        } catch (ConnectException $e) {
            // Only retry on connection timeouts
            Log::error('SimpleTexting network connectivity error', [
                'phone' => $this->phone,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            withScope(function (SentryScope $scope) use ($e): void {
                $scope->setExtra('phone', $this->phone);
                $scope->setExtra('attempt', $this->attempts());
                captureException($e);
            });

            $this->notifyAdminsOnFinalAttempt($e->getMessage());
            throw $e;
        } catch (Exception $e) {
            // Log and fail immediately for all other errors
            Log::error('SimpleTexting error', [
                'phone' => $this->phone,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            withScope(function (SentryScope $scope) use ($e): void {
                $scope->setExtra('phone', $this->phone);
                $scope->setExtra('attempt', $this->attempts());
                captureException($e);
            });

            $this->fail($e);
        }
    }
}
