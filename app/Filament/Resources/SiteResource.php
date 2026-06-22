<?php

namespace App\Filament\Resources;

use App\Enums\SiteStatus;
use App\Filament\Resources\SiteResource\Pages;
use App\Filament\Resources\SiteResource\RelationManagers;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Sites';

    protected static ?string $modelLabel = 'Site';

    protected static ?string $pluralModelLabel = 'Sites';

    protected static ?string $navigationGroup = 'Betrieb';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $offline = static::getModel()::where('is_archived', false)
            ->where('status', SiteStatus::Offline->value)
            ->count();

        return $offline > 0 ? (string) $offline : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Stammdaten')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Kunde')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('site_id')
                        ->label('Site-ID (fachlich)')
                        ->helperText('Muss exakt der OPS_REPORTER_SITE_ID in der wp-config.php entsprechen.')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(191),

                    Forms\Components\TextInput::make('label')
                        ->label('Anzeigename')
                        ->required(),

                    Forms\Components\TextInput::make('url')
                        ->label('URL')
                        ->url()
                        ->required(),

                    Forms\Components\Select::make('cms_type')
                        ->label('CMS-Typ')
                        ->options(['wordpress' => 'WordPress', 'extern' => 'Extern (kein Plugin)'])
                        ->default('wordpress')
                        ->live()
                        ->required(),

                    Forms\Components\TagsInput::make('tags')
                        ->label('Tags'),
                ]),

            Forms\Components\Section::make('Hosting & Paket')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('hosted_by_us')->label('Hosting bei uns')->default(true),
                    Forms\Components\Toggle::make('domain_by_us')->label('Domain bei uns')->default(true),
                    Forms\Components\TextInput::make('package_tier')->label('Paket-Tier'),
                    Forms\Components\TextInput::make('update_interval_days')->label('Update-Intervall (Tage)')->numeric(),
                    Forms\Components\TextInput::make('sla_hours')->label('SLA (Stunden)')->numeric(),
                ]),

            Forms\Components\Section::make('Abläufe')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('ssl_expires_at')->label('SSL läuft ab am')->native(false),
                    Forms\Components\DatePicker::make('domain_expires_at')->label('Domain läuft ab am')->native(false),
                ]),

            Forms\Components\Section::make('Reporter-Secret')
                ->description('Wird verschlüsselt gespeichert. In die wp-config.php der Site als OPS_REPORTER_SECRET eintragen.')
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('secret')
                        ->label('Shared Secret (HMAC)')
                        ->password()
                        ->revealable()
                        ->helperText('Leer lassen, um das bestehende Secret zu behalten. „Neu generieren" rotiert es.')
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('generate')
                                ->icon('heroicon-m-arrow-path')
                                ->label('Neu generieren')
                                ->action(fn (Forms\Set $set) => $set('secret', bin2hex(random_bytes(32))))
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('label')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Site')
                    ->description(fn (Site $r) => $r->url)
                    ->searchable(['label', 'url', 'site_id'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (SiteStatus $state) => $state->label())
                    ->color(fn (SiteStatus $state) => $state->color())
                    ->icon(fn (SiteStatus $state) => $state->icon()),

                Tables\Columns\TextColumn::make('wp_version')->label('WP')->placeholder('–')->toggleable(),
                Tables\Columns\TextColumn::make('php_version')->label('PHP')->placeholder('–')->toggleable(),

                Tables\Columns\TextColumn::make('pending_updates')
                    ->label('Updates')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ssl_expires_at')
                    ->label('SSL')
                    ->formatStateUsing(fn ($state) => static::daysLabel($state))
                    ->color(fn (Site $r) => static::expiryColor($r->ssl_expires_at, 21))
                    ->placeholder('–')
                    ->sortable(),

                Tables\Columns\TextColumn::make('domain_expires_at')
                    ->label('Domain')
                    ->formatStateUsing(fn ($state) => static::daysLabel($state))
                    ->color(fn (Site $r) => static::expiryColor($r->domain_expires_at, 30))
                    ->placeholder('–')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Zuletzt gesehen')
                    ->since()
                    ->placeholder('nie')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(SiteStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),

                Tables\Filters\SelectFilter::make('customer')
                    ->label('Kunde')
                    ->relationship('customer', 'name')
                    ->searchable()->preload(),

                Tables\Filters\TernaryFilter::make('pending_updates')
                    ->label('Hat Updates')
                    ->queries(
                        true: fn ($q) => $q->where('pending_updates', '>', 0),
                        false: fn ($q) => $q->where('pending_updates', 0),
                        blank: fn ($q) => $q,
                    ),
            ])
            // Standardmäßig keine archivierten Sites.
            ->modifyQueryUsing(fn ($query) => $query->where('is_archived', false))
            ->striped()
            // Klick auf die Zeile öffnet die Detailansicht.
            ->recordUrl(fn (Site $record) => static::getUrl('view', ['record' => $record]))
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Offboarding: archivieren statt löschen (Ingest weist die Site danach ab).
                    Tables\Actions\BulkAction::make('archive')
                        ->label('Archivieren')
                        ->icon('heroicon-m-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_archived' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PluginsRelationManager::class,
            RelationManagers\TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'view'   => Pages\ViewSite::route('/{record}'),
            'edit'   => Pages\EditSite::route('/{record}/edit'),
        ];
    }

    /* -------- Anzeige-Helfer -------- */

    protected static function daysLabel($date): string
    {
        if (! $date) {
            return '–';
        }
        $d = (int) round(now()->diffInDays($date, false));
        if ($d < 0) {
            return 'abgelaufen';
        }
        return "{$d} T";
    }

    protected static function expiryColor($date, int $warnDays): ?string
    {
        if (! $date) {
            return null;
        }
        $d = (int) round(now()->diffInDays($date, false));
        if ($d < 7) {
            return 'danger';
        }
        if ($d <= $warnDays) {
            return 'warning';
        }
        return null;
    }
}
