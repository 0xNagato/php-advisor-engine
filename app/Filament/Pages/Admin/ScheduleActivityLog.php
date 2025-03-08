<?php

namespace App\Filament\Pages\Admin;

use App\Models\Venue;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ScheduleActivityLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.pages.admin.schedule-activity-log';

    protected static ?string $title = 'Schedule Activity Log';

    protected static ?string $navigationLabel = 'Schedule Activity Log';

    protected static ?string $slug = 'admin/schedule-activity-log';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationGroup = 'Advanced Tools';

    public ?int $selectedVenueId = null;

    public function mount(): void
    {
        $this->form->fill([
            'selectedVenueId' => $this->selectedVenueId,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('selectedVenueId')
                ->label('Filter by Venue')
                ->options(Venue::query()->orderBy('name')->pluck('name', 'id'))
                ->live()
                ->searchable()
                ->afterStateUpdated(function () {
                    $this->resetTable();
                })
                ->placeholder('All Venues')
                ->preload(),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasActiveRole('super_admin');
    }

    public function table(Table $table): Table
    {
        $query = Activity::query()
            ->where(function ($query) {
                $query->where('description', 'Schedule template updated')
                    ->orWhere('description', 'Schedule override updated');
            })
            ->with(['subject', 'causer'])
            ->latest();

        if ($this->selectedVenueId) {
            $query->where('subject_id', $this->selectedVenueId)
                ->where('subject_type', Venue::class);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j Y, g:ia')
                    ->timezone(auth()->user()?->timezone ?? config('app.timezone'))
                    ->sortable(),
                TextColumn::make('subject.name')
                    ->label('Venue')
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->sortable(),
                ViewColumn::make('properties')
                    ->label('Details')
                    ->view('partials.schedule-activity-details')
                    ->extraAttributes(['class' => 'w-full']),
            ])
            ->filters([])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->striped();
    }
}
