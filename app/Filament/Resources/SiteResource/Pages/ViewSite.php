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
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Artisan;

class ViewSite extends ViewRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('probeExpiry')
                ->label('SSL/Domain prüfen')
                ->icon('heroicon-o-shield-check')
                ->color('gray')
                ->action(function () {
                    Artisan::call('sites:probe-expiry', ['--site' => $this->record->site_id]);
                    $this->record->refresh();

                    Notification::make()
                        ->success()
                        ->title('Geprüft')
                        ->body('SSL-/Domain-Ablauf wurde aktualisiert.')
                        ->send();
                }),
            Actions\EditAction::make(),
        ];
    }

    /*
     | Layout nach UX-Prinzipien: Wichtigstes zuerst, dichte scannbare Zonen,
     | Detail-Bericht eingeklappt (Progressive Disclosure). Deep-Dive (Pakete/
     | Plugins/Aufgaben) liegt in den Relation-Manager-Tabs direkt darunter.
     */
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Grid::make(['default' => 1, 'lg' => 3])->schema([

                // ---- Linke Spalte (2/3): Fakten & Handlungsbedarf -------------
                Group::make([
                    // Kompakter Überblick: die wichtigsten Fakten dicht in einem Raster.
                    Section::make('Überblick')
                        ->icon('heroicon-o-globe-alt')
                        ->columns(3)
                        ->schema([
                            TextEntry::make('status')
                                ->label('Status')->badge()
                                ->formatStateUsing(fn ($state) => $state->label())
                                ->color(fn ($state) => $state->color()),
                            TextEntry::make('customer.name')->label('Kunde')->placeholder('–'),
                            TextEntry::make('last_seen_at')->label('Zuletzt gesehen')->since()->placeholder('nie'),

                            TextEntry::make('wp_version')->label('WordPress')->placeholder('–'),
                            TextEntry::make('php_version')->label('PHP')->placeholder('–'),
                            TextEntry::make('pending_updates')->label('Offene Updates')->badge()
                                ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),

                            TextEntry::make('ssl_expires_at')->label('SSL läuft ab')->date('d.m.Y')->placeholder('–'),
                            TextEntry::make('domain_expires_at')->label('Domain läuft ab')->date('d.m.Y')->placeholder('–'),
                            TextEntry::make('url')->label('URL')->url(fn ($record) => $record->url, true)->limit(28),
                        ]),

                    // Handlungsbedarf & Pakete kompakt nebeneinander (zweite Zone).
                    Grid::make(2)->schema([
                        Section::make('Handlungsbedarf')
                            ->icon('heroicon-o-bolt')
                            ->schema([
                                TextEntry::make('open_tasks')
                                    ->label('Offene Aufgaben')->badge()->color('warning')
                                    ->state(fn (Site $record) => $record->openTasks()
                                        ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
                                        ->pluck('title')->all())
                                    ->placeholder('keine'),
                                TextEntry::make('plugin_updates')
                                    ->label('Plugins mit Updates')->badge()->color('warning')
                                    ->state(fn (Site $record) => $record->plugins()
                                        ->where('update_available', true)->pluck('name')->all())
                                    ->placeholder('alle aktuell'),
                            ]),

                        Section::make('Pakete')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                TextEntry::make('packages_booked')
                                    ->label('Gebucht')->badge()->color('success')
                                    ->state(fn (Site $record) => $record->packages->where('pivot.state', 'booked')->pluck('name')->all())
                                    ->placeholder('keine'),
                                TextEntry::make('packages_declined')
                                    ->label('Abgewählt')->badge()->color('gray')
                                    ->state(fn (Site $record) => $record->packages->where('pivot.state', 'declined')->pluck('name')->all())
                                    ->placeholder('keine'),
                            ]),
                    ]),

                    // Detail-Bericht: standardmäßig eingeklappt (Progressive Disclosure).
                    Section::make('Letzter Bericht (Details)')
                        ->icon('heroicon-o-document-text')
                        ->description('Werte aus dem zuletzt empfangenen Reporter-Snapshot.')
                        ->collapsible()
                        ->collapsed()
                        ->columns(3)
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

                // ---- Rechte Spalte (1/3): Live-Vorschau -----------------------
                Group::make([
                    Section::make('Vorschau')
                        ->icon('heroicon-o-eye')
                        ->schema([
                            ViewEntry::make('preview')
                                ->hiddenLabel()
                                ->view('filament.infolists.site-preview'),
                        ]),
                ])->columnSpan(['default' => 1, 'lg' => 1]),
            ]),
        ]);
    }
}
