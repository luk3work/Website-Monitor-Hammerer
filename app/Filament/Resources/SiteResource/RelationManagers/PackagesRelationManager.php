<?php

namespace App\Filament\Resources\SiteResource\RelationManagers;

use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Websitepakete pro Site: anhaken (gebucht) oder explizit abwählen (declined).
 * Beim Buchen werden Ausschlüsse und Abhängigkeiten geprüft.
 */
class PackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'packages';

    protected static ?string $title = 'Pakete';

    protected static ?string $icon = 'heroicon-o-cube';

    private const STATES = [
        'booked'   => 'Gebucht',
        'declined' => 'Abgewählt',
    ];

    private const GROUP_LABELS = [
        'hosting_tier' => 'Hosting',
        'addon'        => 'Zusatzpakete',
        'update'       => 'Update-Service',
        'seo'          => 'SEO',
        'performance'  => 'Performance',
        'datenschutz'  => 'Datenschutz',
        'security'     => 'Sicherheit',
        'a11y'         => 'Barrierefreiheit',
        'reporting'    => 'Reporting',
    ];

    /** Formular für Bearbeiten des Pivot-Zustands. */
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('state')
                ->label('Status')
                ->options(self::STATES)
                ->default('booked')
                ->required()
                ->native(false),
            Forms\Components\Textarea::make('note')
                ->label('Notiz')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultGroup(
                \Filament\Tables\Grouping\Group::make('group')
                    ->label('Gruppe')
                    ->getTitleFromRecordUsing(fn (Package $record) => self::GROUP_LABELS[$record->group] ?? $record->group)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Paket')
                    ->description(fn (Package $record) => $record->priceLabel())
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Package $record) => self::STATES[$record->pivot->state] ?? $record->pivot->state)
                    ->color(fn (Package $record) => $record->pivot->state === 'declined' ? 'gray' : 'success')
                    ->icon(fn (Package $record) => $record->pivot->state === 'declined' ? 'heroicon-m-x-mark' : 'heroicon-m-check'),

                Tables\Columns\TextColumn::make('pivot.note')
                    ->label('Notiz')
                    ->getStateUsing(fn (Package $record) => $record->pivot->note)
                    ->placeholder('–')
                    ->toggleable()
                    ->wrap(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Paket zuordnen')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn ($query) => $query->where('is_active', true)->orderBy('sort'))
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()->label('Paket'),
                        Forms\Components\Select::make('state')
                            ->label('Status')
                            ->options(self::STATES)
                            ->default('booked')
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('note')->label('Notiz')->rows(2),
                    ])
                    ->before(function (array $data, RelationManager $livewire, Tables\Actions\AttachAction $action) {
                        $this->guardDependencies($data, $livewire, $action);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Status'),
                Tables\Actions\DetachAction::make()->label('Entfernen'),
            ])
            ->emptyStateHeading('Noch keine Pakete zugeordnet')
            ->emptyStateDescription('Ordne gebuchte Pakete zu oder markiere bewusst abgewählte.');
    }

    /** Prüft beim Buchen Ausschlüsse und Voraussetzungen; bricht bei Konflikt ab. */
    private function guardDependencies(array $data, RelationManager $livewire, Tables\Actions\AttachAction $action): void
    {
        $state = $data['state'] ?? 'booked';
        if ($state !== 'booked') {
            return; // Abgewählte/Notizen brauchen keine Abhängigkeitsprüfung.
        }

        $package = Package::find($data['recordId'] ?? null);
        if (! $package) {
            return;
        }

        $bookedKeys = $livewire->getOwnerRecord()
            ->packages()
            ->wherePivot('state', 'booked')
            ->pluck('packages.key')
            ->all();

        // Ausschlüsse: keines der ausgeschlossenen Pakete darf gebucht sein.
        $conflict = array_intersect((array) $package->excludes, $bookedKeys);
        if ($conflict) {
            Notification::make()->danger()
                ->title('Paket-Konflikt')
                ->body('Schließt sich mit bereits gebuchten Paketen aus: ' . implode(', ', $conflict))
                ->send();
            $action->halt();
        }

        // Voraussetzungen (alle): müssen gebucht sein.
        $missing = array_diff((array) $package->requires, $bookedKeys);
        if ($missing) {
            Notification::make()->warning()
                ->title('Voraussetzung fehlt')
                ->body('Setzt voraus (alle): ' . implode(', ', $missing))
                ->send();
            $action->halt();
        }

        // Voraussetzung (mindestens eines): genau dann Fehler, wenn keines gebucht.
        if (! empty($package->requires_any) && ! array_intersect((array) $package->requires_any, $bookedKeys)) {
            Notification::make()->warning()
                ->title('Voraussetzung fehlt')
                ->body('Setzt mindestens eines voraus: ' . implode(', ', $package->requires_any))
                ->send();
            $action->halt();
        }
    }
}
