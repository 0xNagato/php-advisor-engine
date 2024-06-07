<?php

namespace App\Livewire;

use App\Filament\Pages\Concierge\ReservationHub;
use App\Filament\Resources\MessageResource\Pages\ListMessages;
use App\Models\Region;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;

/**
 * @property Form $form
 */
class ConciergeUserMenu extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.concierge-user-menu';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Toggle::make('simpleMode')
                ->label(fn () => new HtmlString('<span class="text-sm">Booking Mode</span>'))
                ->live()
                ->default(fn () => session('simpleMode', false))
                ->afterStateUpdated(function (Get $get) {
                    session(['simpleMode' => $get('simpleMode')]);
                    $this->dispatch('simple-mode-toggled', $get('simpleMode'));
                    if ($get('simpleMode')) {
                        $this->redirect(ReservationHub::getUrl());
                    } else {
                        $this->redirect(ListMessages::getUrl());
                    }
                }),
            Select::make('region')
                ->hiddenLabel()
                ->live()
                ->placeholder('Select a region')
                ->options(Region::active()->orderBy('id')->pluck('name', 'id'))
                ->extraAttributes(['class' => 'text-sm'])
                ->afterStateUpdated(function (Get $get) {
                    session(['region' => $get('region')]);
                    $this->dispatch('region-changed', $get('region'));
                })
                ->default(session('region', ''))
                ->selectablePlaceholder(false)
                ->searchable(),
        ])
            ->statePath('data')
            ->extraAttributes(['class' => 'inline-form']);
    }
}
