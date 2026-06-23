<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Die "Braucht Handlung"-Liste: das Herz des Cockpits.
 * Zeigt alle offenen Aufgaben quer über die Sites, kritisch zuerst.
 */
class NeedsActionTable extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 4];

    protected static ?string $heading = 'Braucht Handlung';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Task::query()
                    ->open()
                    ->with('site')
                    // kritisch (0) vor warning (1) vor info (2):
                    ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
                    ->orderBy('due_date')
            )
            ->emptyStateHeading('Alles erledigt')
            ->emptyStateDescription('Keine offenen Aufgaben – schön ruhig hier.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('severity')
                    ->label('')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),

                TextColumn::make('title')
                    ->label('Aufgabe')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('site.label')
                    ->label('Site')
                    ->description(fn (Task $r) => $r->site?->url)
                    ->url(fn (Task $r) => $r->site
                        ? \App\Filament\Resources\SiteResource::getUrl('view', ['record' => $r->site])
                        : null)
                    ->color('primary'),

                TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('due_date')
                    ->label('Frist')
                    ->date('d.m.Y')
                    ->color(fn (Task $r) => $r->due_date && $r->due_date->isPast() ? 'danger' : null)
                    ->placeholder('–'),
            ])
            ->actions([
                Action::make('done')
                    ->label('Erledigt')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Task $record) => $record->update([
                        'status'      => 'done',
                        'resolved_at' => now(),
                    ])),
            ]);
    }
}
