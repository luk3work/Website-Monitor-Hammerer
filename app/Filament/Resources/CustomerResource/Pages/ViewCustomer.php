<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Kunde')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label('Name'),
                    TextEntry::make('company')->label('Firma')->placeholder('–'),
                    TextEntry::make('email')->label('E-Mail')->placeholder('–')->copyable(),
                    TextEntry::make('phone')->label('Telefon')->placeholder('–'),
                    IconEntry::make('is_active')->label('Aktiv')->boolean(),
                    TextEntry::make('sites_count')
                        ->label('Sites')
                        ->state(fn ($record) => $record->sites()->count()),
                ]),

            Section::make('Notizen')
                ->schema([
                    TextEntry::make('notes')->hiddenLabel()->placeholder('–')->columnSpanFull(),
                ]),
        ]);
    }
}
