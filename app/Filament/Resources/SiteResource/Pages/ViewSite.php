<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use Filament\Actions;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSite extends ViewRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Überblick')
                ->columns(3)
                ->schema([
                    TextEntry::make('label')->label('Site'),
                    TextEntry::make('url')->label('URL')->url(fn ($record) => $record->url, true),
                    TextEntry::make('customer.name')->label('Kunde'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state->label())
                        ->color(fn ($state) => $state->color()),
                    TextEntry::make('package_tier')->label('Paket')->placeholder('–'),
                    TextEntry::make('last_seen_at')->label('Zuletzt gesehen')->since()->placeholder('nie'),
                ]),

            Section::make('Technik')
                ->columns(3)
                ->schema([
                    TextEntry::make('wp_version')->label('WordPress')->placeholder('–'),
                    TextEntry::make('php_version')->label('PHP')->placeholder('–'),
                    TextEntry::make('pending_updates')->label('Offene Updates')->badge()
                        ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                ]),

            Section::make('Abläufe')
                ->columns(2)
                ->schema([
                    TextEntry::make('ssl_expires_at')->label('SSL läuft ab')->date('d.m.Y')->placeholder('–'),
                    TextEntry::make('domain_expires_at')->label('Domain läuft ab')->date('d.m.Y')->placeholder('–'),
                ]),

            // Details aus dem zuletzt empfangenen Reporter-Snapshot (read-only).
            Section::make('Letzter Bericht')
                ->description('Werte aus dem zuletzt empfangenen Snapshot des Reporter-Plugins.')
                ->columns(3)
                ->schema([
                    TextEntry::make('latestSnapshot.mysql_version')->label('MySQL/MariaDB')->placeholder('–'),
                    IconEntry::make('latestSnapshot.https')->label('HTTPS')->boolean(),
                    IconEntry::make('latestSnapshot.is_multisite')->label('Multisite')->boolean(),
                    TextEntry::make('latestSnapshot.plugins_active')->label('Plugins aktiv')->placeholder('–'),
                    TextEntry::make('latestSnapshot.plugins_total')->label('Plugins gesamt')->placeholder('–'),
                    TextEntry::make('latestSnapshot.plugins_update')->label('Plugin-Updates')->badge()
                        ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')->placeholder('–'),
                    TextEntry::make('latestSnapshot.reporter_version')->label('Reporter-Version')->placeholder('–'),
                    TextEntry::make('latestSnapshot.collected_at')->label('Erhoben am')->dateTime('d.m.Y H:i')->placeholder('–'),
                    TextEntry::make('latestSnapshot.received_at')->label('Empfangen am')->dateTime('d.m.Y H:i')->placeholder('–'),
                ]),
        ]);
    }
}
