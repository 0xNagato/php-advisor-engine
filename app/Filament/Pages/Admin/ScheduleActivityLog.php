<?php

namespace App\Filament\Pages\Admin;

use App\Models\Venue;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('subject.name')
                    ->label('Venue')
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->sortable(),
                TextColumn::make('properties')
                    ->label('Details')
                    ->formatStateUsing(function ($state, $record) {
                        // We need to directly access the properties column from the database
                        $properties = json_decode($record->getRawOriginal('properties'), true);

                        if (! $properties || ! is_array($properties)) {
                            return '<div class="italic text-gray-500">No details available</div>';
                        }

                        $output = '<div class="space-y-3">';

                        // Add badges at the top in a row
                        $output .= '<div class="flex items-center gap-2">';

                        // Time slot info with US-style time
                        if (isset($properties['time'])) {
                            $dayInfo = '';
                            if (isset($properties['day_of_week'])) {
                                $dayInfo = ucfirst($properties['day_of_week']);
                            } elseif (isset($properties['booking_date'])) {
                                $dayInfo = 'Date '.date('M j, Y', strtotime($properties['booking_date']));
                            } else {
                                $dayInfo = 'Unknown day';
                            }

                            // Convert 24h time to 12h am/pm format
                            $time = $properties['time'];
                            $formattedTime = date('g:ia', strtotime($time));

                            $output .= '<div class="font-medium">'.$formattedTime.' on '.$dayInfo.'</div>';
                        }

                        // Spacer to push badges to the right
                        $output .= '<div class="flex-grow"></div>';

                        // Type badge
                        $typeBadgeClass = $record->description === 'Schedule template updated'
                            ? 'bg-primary-100 text-primary-700'
                            : 'bg-warning-100 text-warning-700';
                        $output .= '<span class="px-2 py-1 text-xs rounded-full '.$typeBadgeClass.'">'.$record->description.'</span>';

                        // Bulk edit indicator
                        $isBulkEdit = $properties['bulk_edit'] ?? false;
                        $output .= $isBulkEdit
                            ? '<span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800">Bulk Edit</span>'
                            : '<span class="px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">Single Edit</span>';

                        $output .= '</div>';

                        // Changes section
                        $output .= '<div class="pt-2 mt-2 border-t">';
                        $output .= '<div class="mb-1 font-medium">Changes:</div>';

                        // For template_update (array format)
                        if ($properties['action'] === 'template_update' && isset($properties['new_data']) && is_array($properties['new_data'])) {
                            foreach ($properties['new_data'] as $item) {
                                if (isset($item['data'])) {
                                    $output .= $this->formatTemplateItemChanges($item);
                                }
                            }
                        }
                        // For override_update (object format with party sizes as keys)
                        elseif ($properties['action'] === 'override_update' && isset($properties['new_data']) && is_array($properties['new_data'])) {
                            foreach ($properties['new_data'] as $partySize => $data) {
                                if ($data !== null) {
                                    $output .= '<div class="p-1 ml-2 text-sm rounded bg-gray-50">';
                                    $output .= '<span class="font-medium">Party of '.$partySize.':</span> ';

                                    // Collect changes
                                    $changes = [];

                                    if (isset($data['prime_time'])) {
                                        $changes[] = '<span class="'.($data['prime_time'] ? 'text-amber-600' : 'text-gray-600').'">'
                                            .($data['prime_time'] ? 'Prime Time' : 'Regular Time').'</span>';
                                    }

                                    if (isset($data['is_available'])) {
                                        $changes[] = '<span class="'.($data['is_available'] ? 'text-green-600' : 'text-red-600').'">'
                                            .($data['is_available'] ? 'Available' : 'Not Available').'</span>';
                                    }

                                    if (isset($data['price_per_head']) && $data['price_per_head'] !== null) {
                                        $changes[] = 'Price: $'.number_format($data['price_per_head'], 2);
                                    }

                                    if (isset($data['minimum_spend_per_guest']) && $data['minimum_spend_per_guest'] > 0) {
                                        $changes[] = 'Min Spend: $'.number_format($data['minimum_spend_per_guest'], 2);
                                    }

                                    if (isset($data['available_tables'])) {
                                        $tables = $data['available_tables'];
                                        $changes[] = 'Tables: '.($tables === 0 || $tables === '0' ? '<span class="text-red-600">None</span>' : $tables);
                                    }

                                    // Show the changes
                                    $output .= implode(' • ', $changes);
                                    $output .= '</div>';
                                }
                            }
                        }
                        $output .= '</div>';

                        // Original data section
                        if (isset($properties['original_data']) && ! empty($properties['original_data'])) {
                            $output .= '<div class="pt-2 mt-2 text-sm text-gray-500 border-t">';
                            $output .= '<div class="mb-1 font-medium">Previous settings:</div>';

                            // For template_update (array format)
                            if ($properties['action'] === 'template_update' && is_array($properties['original_data'])) {
                                $count = count($properties['original_data']);
                                foreach ($properties['original_data'] as $index => $item) {
                                    // Only show first 2 items to save space
                                    if ($index >= 2) {
                                        if ($index == 2) {
                                            $output .= '<div class="ml-2 italic">+ '.($count - 2).' more party sizes...</div>';
                                        }

                                        continue;
                                    }

                                    if ($item !== null) {
                                        $originalChanges = [];

                                        if (isset($item['prime_time'])) {
                                            $originalChanges[] = ($item['prime_time'] ? 'Prime Time' : 'Regular Time');
                                        }

                                        if (isset($item['is_available'])) {
                                            $originalChanges[] = ($item['is_available'] ? 'Available' : 'Not Available');
                                        }

                                        if (isset($item['price_per_head']) && $item['price_per_head'] !== null) {
                                            $originalChanges[] = 'Price: $'.number_format($item['price_per_head'], 2);
                                        }

                                        if (isset($item['minimum_spend_per_guest']) && $item['minimum_spend_per_guest'] > 0) {
                                            $originalChanges[] = 'Min Spend: $'.number_format($item['minimum_spend_per_guest'], 2);
                                        }

                                        if (isset($item['available_tables'])) {
                                            $originalChanges[] = 'Tables: '.$item['available_tables'];
                                        }

                                        if (! empty($originalChanges)) {
                                            $output .= '<div class="ml-2">';
                                            if (isset($item['id'])) {
                                                $output .= '<span class="opacity-75">ID '.$item['id'].':</span> ';
                                            }
                                            $output .= implode(' • ', $originalChanges);
                                            $output .= '</div>';
                                        }
                                    }
                                }
                            }
                            // For override_update (object format with party sizes as keys)
                            elseif ($properties['action'] === 'override_update' && is_array($properties['original_data'])) {
                                $partySizes = array_keys($properties['original_data']);
                                $count = count($partySizes);

                                foreach ($partySizes as $index => $partySize) {
                                    // Only show first 2 items to save space
                                    if ($index >= 2) {
                                        if ($index == 2) {
                                            $output .= '<div class="ml-2 italic">+ '.($count - 2).' more party sizes...</div>';
                                        }

                                        continue;
                                    }

                                    $data = $properties['original_data'][$partySize];
                                    if ($data === null) {
                                        $output .= '<div class="ml-2">Party of '.$partySize.': <span class="italic">No previous data</span></div>';
                                    } else {
                                        $originalChanges = [];

                                        if (isset($data['prime_time'])) {
                                            $originalChanges[] = ($data['prime_time'] ? 'Prime Time' : 'Regular Time');
                                        }

                                        if (isset($data['is_available'])) {
                                            $originalChanges[] = ($data['is_available'] ? 'Available' : 'Not Available');
                                        }

                                        if (isset($data['price_per_head']) && $data['price_per_head'] !== null) {
                                            $originalChanges[] = 'Price: $'.number_format($data['price_per_head'], 2);
                                        }

                                        if (isset($data['minimum_spend_per_guest']) && $data['minimum_spend_per_guest'] > 0) {
                                            $originalChanges[] = 'Min Spend: $'.number_format($data['minimum_spend_per_guest'], 2);
                                        }

                                        if (isset($data['available_tables'])) {
                                            $originalChanges[] = 'Tables: '.$data['available_tables'];
                                        }

                                        if (! empty($originalChanges)) {
                                            $output .= '<div class="ml-2">';
                                            $output .= '<span class="font-medium">Party of '.$partySize.':</span> ';
                                            $output .= implode(' • ', $originalChanges);
                                            $output .= '</div>';
                                        }
                                    }
                                }
                            }

                            $output .= '</div>';
                        }

                        $output .= '</div>';

                        return $output;
                    })
                    ->html(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j Y, g:ia')
                    ->timezone(auth()->user()?->timezone ?? config('app.timezone'))
                    ->sortable(),
            ])
            ->filters([])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->striped();
    }

    private function formatTemplateItemChanges(array $item): string
    {
        $output = '<div class="p-1 ml-2 text-sm rounded bg-gray-50">';

        // Party size indicator
        if (isset($item['party_size'])) {
            $output .= '<span class="font-medium">Party of '.$item['party_size'].':</span> ';
        }

        // Collect changes
        $changes = [];

        if (isset($item['data']['is_prime'])) {
            $changes[] = '<span class="'.($item['data']['is_prime'] ? 'text-amber-600' : 'text-gray-600').'">'
                .($item['data']['is_prime'] ? 'Prime Time' : 'Regular Time').'</span>';
        }

        if (isset($item['data']['is_available'])) {
            $changes[] = '<span class="'.($item['data']['is_available'] ? 'text-green-600' : 'text-red-600').'">'
                .($item['data']['is_available'] ? 'Available' : 'Not Available').'</span>';
        }

        if (isset($item['data']['price_per_head']) && $item['data']['price_per_head'] !== null) {
            $changes[] = 'Price: $'.number_format($item['data']['price_per_head'], 2);
        }

        if (isset($item['data']['minimum_spend_per_guest']) && $item['data']['minimum_spend_per_guest'] > 0) {
            $changes[] = 'Min Spend: $'.number_format($item['data']['minimum_spend_per_guest'], 2);
        }

        if (isset($item['data']['available_tables'])) {
            $tables = $item['data']['available_tables'];
            $changes[] = 'Tables: '.($tables === '0' ? '<span class="text-red-600">None</span>' : $tables);
        }

        // Show the changes
        $output .= implode(' • ', $changes);
        $output .= '</div>';

        return $output;
    }
}
