<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use App\Models\Message;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected ?string $heading = '';

    protected static string $view = 'filament.pages.messages.list-messages';
    

    /**
     * @var Collection<Message>
     */
    public Collection $messages;

    public function mount(): void
    {
        $this->messages = Message::with('announcement.sender')
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        // Mark all unread messages as read
        Message::query()->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
