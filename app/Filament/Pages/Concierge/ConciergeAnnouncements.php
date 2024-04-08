<?php

namespace App\Filament\Pages\Concierge;

use Filament\Pages\Page;
use Illuminate\Support\Facades\URL;

class ConciergeAnnouncements extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?int $navigationSort = -5;

    protected static ?string $slug = 'concierge/announcements';
    protected static string $view = 'filament.pages.concierge.concierge-welcome';

    protected static ?string $title = 'Announcements';
    protected ?string $heading = 'Announcements';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('concierge');
    }

    public function generateDemoLoginLink(): string
    {
        $url = URL::signedRoute(
            'demo.auth',
            ['user_id' => auth()->id()],
            absolute: false
        );

        return "https://demo.primavip.co$url";
    }
}
