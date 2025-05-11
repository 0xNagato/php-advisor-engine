<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use App\Models\Message;
use Filament\Resources\Pages\ViewRecord;

/**
 * @method Message resolveRecord($record)
 */
class ViewMessage extends ViewRecord
{
    protected static string $resource = MessageResource::class;

    protected ?string $heading = '';

    protected static string $view = 'filament.pages.messages.view-message';

    public static function canAccess(array $parameters = []): bool
    {
        return true;
    }

    public Message $message;

    public function mount(string|int $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->message = $this->record;

        if (is_null($this->message->read_at)) {
            $this->message->update(['read_at' => now()]);
        }
    }
}
