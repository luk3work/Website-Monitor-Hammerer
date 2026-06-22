<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Enums\SiteStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SitesRelationManager extends RelationManager
{
    protected static string $relationship = 'sites';

    protected static ?string $title = 'Sites';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('label')->label('Site')->description(fn ($r) => $r->url),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (SiteStatus $s) => $s->label())
                    ->color(fn (SiteStatus $s) => $s->color()),
                Tables\Columns\TextColumn::make('package_tier')->label('Paket')->placeholder('–'),
                Tables\Columns\TextColumn::make('pending_updates')->label('Updates')->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Öffnen')
                    ->url(fn ($record) => \App\Filament\Resources\SiteResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public function canCreate(): bool
    {
        return false;
    }
}
