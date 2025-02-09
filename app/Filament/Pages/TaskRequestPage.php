<?php

namespace App\Filament\Pages;

use App\Actions\CreateAsanaTaskAction;
use App\Actions\ParseTaskRequestAction;
use App\Models\User;
use App\Notifications\NewTaskCreated;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TaskRequestPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static string $view = 'filament.pages.task-request-page';

    public bool $isLoading = false;

    // Livewire properties for the chat interface
    public $inputMessage = '';

    public $chatHistory = "System: Hi! Type your task request below.\n";

    public $parsedTask = null;

    public $confirmationStep = false;

    public $taskRequirements = [];

    public ?array $originalTask = null;

    public ?string $taskUrl = null;

    public static function canAccess(): bool
    {
        // only users with id of 1 or 2 can access this page
        return in_array(auth()->user()?->id, [1, 2]);
    }

    protected static ?string $navigationGroup = 'Advanced Tools';

    /**
     * Called when the boss submits a new request.
     */
    public function submitRequest()
    {
        $this->isLoading = true;

        $this->validate([
            'inputMessage' => 'required|string',
        ]);

        $bossMessage = $this->inputMessage;
        $this->chatHistory .= "\n\nBoss: ".$bossMessage."\n";
        $this->inputMessage = '';

        // Dispatch event for auto-scrolling
        $this->dispatch('chat-updated');

        if ($this->confirmationStep) {
            // Add the new requirement to our array
            $this->taskRequirements[] = $bossMessage;

            // Build the combined message with original context
            $combinedMessage = "Original Task: {$this->originalTask['name']}\n\n";
            $combinedMessage .= "Original Description: {$this->originalTask['notes']}\n\n";
            $combinedMessage .= "Additional Requirements:\n";
            foreach ($this->taskRequirements as $index => $requirement) {
                $combinedMessage .= ($index + 1).". {$requirement}\n";
            }

            $this->parsedTask = ParseTaskRequestAction::run($combinedMessage);

            if (! $this->parsedTask) {
                $this->chatHistory .= "\n\n❌ System: Unable to parse your request. Please try again.";
                $this->confirmationStep = false;
                $this->isLoading = false;

                return;
            }

            // Enhanced task confirmation formatting
            $this->chatHistory .= "\n\n📋 System: Task Details:\n";
            $this->chatHistory .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $this->chatHistory .= "📌 Name: {$this->parsedTask['name']}\n\n";
            $this->chatHistory .= "📝 Description:\n{$this->parsedTask['notes']}\n";
            $this->chatHistory .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $this->chatHistory .= "\n✨ Please confirm or modify your request.";

            $this->isLoading = false;

            return;
        }

        // Store original task details
        $this->parsedTask = ParseTaskRequestAction::run($bossMessage);
        if ($this->parsedTask) {
            $this->originalTask = $this->parsedTask;
        }

        // Enhanced initial task formatting
        $this->chatHistory .= "\n\n📋 System: Task Details:\n";
        $this->chatHistory .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $this->chatHistory .= "📌 Name: {$this->parsedTask['name']}\n\n";
        $this->chatHistory .= "📝 Description:\n{$this->parsedTask['notes']}\n";
        $this->chatHistory .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $this->chatHistory .= "\n✨ Please confirm or modify your request.";

        $this->confirmationStep = true;
        $this->isLoading = false;
    }

    public function confirmTask()
    {
        $this->isLoading = true;

        // Create the task in Asana
        $response = CreateAsanaTaskAction::run($this->parsedTask);

        if ($response['success']) {
            $this->taskUrl = $response['task_url'];  // Store the task URL
            // Send notification email
            $notifyUsers = User::query()->whereIn('id', [1, 2])->get();
            foreach ($notifyUsers as $user) {
                $user->notify(new NewTaskCreated(
                    taskName: $this->parsedTask['name'],
                    taskNotes: $this->parsedTask['notes'],
                    taskUrl: $response['task_url'],
                    creatorName: auth()->user()->name,
                ));
            }

            $this->chatHistory .= "\n\n✅ System: Task created successfully in Asana!";

            Notification::make()
                ->title('Task Created')
                ->success()
                ->body('Task details have been emailed to you.')
                ->send();
        } else {
            $this->chatHistory .= "\n\n❌ System: Failed to create task in Asana.";
        }

        $this->confirmationStep = false;
        $this->parsedTask = null;
        $this->inputMessage = '';
        $this->isLoading = false;

        // Dispatch event for auto-scrolling
        $this->dispatch('chat-updated');
    }
}
