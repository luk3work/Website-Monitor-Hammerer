<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\Site;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
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
            // Zweispaltiges Layout: links die technischen Details, rechts die
            // "interessanten" Inhalte (Pakete, Aufgaben, Updates) prominent sichtbar.
            Grid::make(['default' => 1, 'lg' => 3])->schema([

                // ---- Linke Spalte: Betrieb & Technik ----
                Group::make([
                    Section::make('Überblick')
                        ->icon('heroicon-o-globe-alt')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('label')->label('Site'),
                            TextEntry::make('url')->label('URL')->url(fn ($record) => $record->url, true),
                            TextEntry::make('customer.name')->label('Kunde'),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state->label())
                                ->color(fn ($state) => $state->color()),
                            TextEntry::make('package_tier')->label('Paket-Tier')->placeholder('–'),
                            TextEntry::make('last_seen_at')->label('Zuletzt gesehen')->since()->placeholder('nie'),
                        ]),

                    Section::make('Technik')
                        ->icon('heroicon-o-cpu-chip')
                        ->columns(3)
                        ->schema([
                            TextEntry::make('wp_version')->label('WordPress')->placeholder('–'),
                            TextEntry::make('php_version')->label('PHP')->placeholder('–'),
                            TextEntry::make('pending_updates')->label('Offene Updates')->badge()
                                ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                        ]),

                    Section::make('Abläufe')
                        ->icon('heroicon-o-calendar-days')
                        ->columns(2)
                        ->schema([
                            TextEntry::make('ssl_expires_at')->label('SSL läuft ab')->date('d.m.Y')->placeholder('–'),
                            TextEntry::make('domain_expires_at')->label('Domain läuft ab')->date('d.m.Y')->placeholder('–'),
                        ]),

                    Section::make('Letzter Bericht')
                        ->icon('heroicon-o-document-text')
                        ->description('Werte aus dem zuletzt empfangenen Reporter-Snapshot.')
                        ->columns(3)
                        ->collapsible()
                        ->schema([
                            TextEntry::make('latestSnapshot.mysql_version')->label('MySQL/MariaDB')->placeholder('–'),
                            IconEntry::make('latestSnapshot.https')->label('HTTPS')->boolean(),
                            IconEntry::make('latestSnapshot.is_multisite')->label('Multisite')->boolean(),
                            TextEntry::make('latestSnapshot.plugins_active')->label('Plugins aktiv')->placeholder('–'),
                            TextEntry::make('latestSnapshot.plugins_total')->label('Plugins gesamt')->placeholder('–'),
                            TextEntry::make('latestSnapshot.reporter_version')->label('Reporter')->placeholder('–'),
                            TextEntry::make('latestSnapshot.collected_at')->label('Erhoben am')->dateTime('d.m.Y H:i')->placeholder('–'),
                            TextEntry::make('latestSnapshot.received_at')->label('Empfangen am')->dateTime('d.m.Y H:i')->placeholder('–'),
                        ]),
                ])->columnSpan(['default' => 1, 'lg' => 2]),

                // ---- Rechte Spalte: das Wesentliche auf einen Blick ----
                Group::make([
                    Section::make('Vorschau')
                        ->icon('heroicon-o-eye')
                        ->schema([
                            ViewEntry::make('preview')
                                ->hiddenLabel()
                                ->view('filament.infolists.site-preview'),
                        ]),

                    Section::make('Pakete')
                        ->icon('heroicon-o-cube')
                        ->schema([
                            TextEntry::make('packages_booked')
                                ->label('Gebucht')
                                ->badge()
                                ->color('success')
                                ->state(fn (Site $record) => $record->packages->where('pivot.state', 'booked')->pluck('name')->all())
                                ->placeholder('keine'),
                            TextEntry::make('packages_declined')
                                ->label('Abgewählt')
                                ->badge()
                                ->color('gray')
                                ->state(fn (Site $record) => $record->packages->where('pivot.state', 'declined')->pluck('name')->all())
                                ->placeholder('keine'),
                        ]),

                    Section::make('Offene Aufgaben')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->schema([
                            TextEntry::make('open_tasks')
                                ->hiddenLabel()
                                ->badge()
                                ->color('warning')
                                ->state(fn (Site $record) => $record->openTasks()
                                    ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
                                    ->pluck('title')->all())
                                ->placeholder('keine offenen Aufgaben'),
                        ]),

                    Section::make('Plugins mit Updates')
                        ->icon('heroicon-o-puzzle-piece')
                        ->schema([
                            TextEntry::make('plugin_updates')
                                ->hiddenLabel()
                                ->badge()
                                ->color('warning')
                                ->state(fn (Site $record) => $record->plugins()
                                    ->where('update_available', true)
                                    ->pluck('name')->all())
                                ->placeholder('alle aktuell'),
                        ]),
                ])->columnSpan(['default' => 1, 'lg' => 1]),
            ]),
        ]);
    }
}
