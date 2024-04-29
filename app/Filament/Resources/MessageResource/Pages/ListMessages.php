<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected ?string $heading = 'Announcements';

    protected static string $view = 'filament.pages.messages.list-messages';

    public Collection $messages;

    public function mount(): void
    {
        $this->messages = auth()->user()->messages()->get();
    }
}
