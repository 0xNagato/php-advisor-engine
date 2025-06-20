<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateUserToken extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'user:create-token
                            {user : The user ID or email to create a token for}
                            {--name= : Token name (default: command-line-token)}
                            {--expires=24 : Hours until the token expires (default: 24)}
                            {--abilities=* : Token abilities (default: ["*"])}';

    /**
     * The console command description.
     */
    protected $description = 'Create a Sanctum token for any user for testing purposes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $tokenName = $this->option('name') ?: 'command-line-token';
        $expiresHours = (int) $this->option('expires');
        $abilities = $this->option('abilities') ?: ['*'];

        $this->info('Creating user token...');
        $this->line("User: {$userIdentifier}");
        $this->line("Token Name: {$tokenName}");
        $this->line("Expires in: {$expiresHours} hours");
        $this->line('Abilities: '.implode(', ', $abilities));

        // Find the user
        $user = $this->findUser($userIdentifier);

        if (! $user) {
            $this->error("User not found: {$userIdentifier}");
            $this->line('Available users:');

            $users = User::orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'email']);

            if ($users->isEmpty()) {
                $this->line('No users found in the database.');
            } else {
                $this->table(
                    ['ID', 'Name', 'Email'],
                    $users->map(fn ($user) => [
                        $user->id,
                        $user->name,
                        $user->email,
                    ])
                );
            }

            return Command::FAILURE;
        }

        // Create the token
        $this->info('Creating token...');

        try {
            $token = $user->createToken(
                $tokenName,
                $abilities,
                now()->addHours($expiresHours)
            );

            $this->displayTokenInfo($token, $user);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to create token: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Find user by ID or email
     */
    private function findUser(string $identifier): ?User
    {
        // Try to find by ID first
        if (is_numeric($identifier)) {
            $user = User::find($identifier);
            if ($user) {
                return $user;
            }
        }

        // Try to find by email
        return User::where('email', $identifier)->first();
    }

    /**
     * Display token information in a formatted way
     */
    private function displayTokenInfo($token, User $user): void
    {
        $this->newLine();
        $this->info('✅ User Token Created Successfully!');
        $this->newLine();

        // Display token information
        $this->line('Token:');
        $this->line('<fg=yellow>'.$token->plainTextToken.'</>');
        $this->newLine();

        // Display user information
        $this->line('User Information:');
        $this->line('ID: '.$user->id);
        $this->line('Name: '.$user->name);
        $this->line('Email: '.$user->email);
        $this->newLine();

        // Display token details
        $this->line('Token Details:');
        $this->line('Name: '.$token->accessToken->name);
        $this->line('Expires At: '.$token->accessToken->expires_at?->toISOString() ?? 'Never');
        $this->line('Abilities: '.implode(', ', $token->accessToken->abilities));
        $this->newLine();

        $this->line('Usage Examples:');
        $this->line('  curl -H "Authorization: Bearer '.$token->plainTextToken.'" '.config('app.url').'/api/me');
        $this->line('  curl -H "Authorization: Bearer '.$token->plainTextToken.'" '.config('app.url').'/api/venues');

        $this->newLine();
        $this->line('⚠️  This token will expire in 24 hours and should only be used for testing.');
    }
}
