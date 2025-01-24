<?php

declare(strict_types=1);

namespace App\Filament\Resources\VenueInvoiceResource\Pages;

use App\Enums\VenueInvoiceStatus;
use App\Filament\Resources\VenueInvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListVenueInvoices extends ListRecords
{
    protected static string $resource = VenueInvoiceResource::class;

    public function getTabs(): array
    {
        return [
            'all' => ListRecords\Tab::make('All')
                ->badge(static::getResource()::getEloquentQuery()->count()),
            'draft' => ListRecords\Tab::make('Draft')
                ->badge(static::getResource()::getEloquentQuery()->where('status', VenueInvoiceStatus::DRAFT)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', VenueInvoiceStatus::DRAFT)),
            'sent' => ListRecords\Tab::make('Sent')
                ->badge(static::getResource()::getEloquentQuery()->where('status', VenueInvoiceStatus::SENT)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', VenueInvoiceStatus::SENT)),
            'paid' => ListRecords\Tab::make('Paid')
                ->badge(static::getResource()::getEloquentQuery()->where('status', VenueInvoiceStatus::PAID)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', VenueInvoiceStatus::PAID)),
            'void' => ListRecords\Tab::make('Void')
                ->badge(static::getResource()::getEloquentQuery()->where('status', VenueInvoiceStatus::VOID)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', VenueInvoiceStatus::VOID)),
        ];
    }
}
