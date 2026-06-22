<?php

namespace App\Filament\Resources\SiteResource\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PluginsRelationManager extends RelationManager
{
    protected static string $relationship = 'plugins';

    protected static ?string $title = 'Plugins / Updates';

    public function form(Form $form): Form
    {
        return $form->schema([
            Toggle::make('hold')->label('Update zurückhalten')
                ->helperText('Wenn das aktuelle Update bekanntermaßen fehlerhaft ist.'),
            TextInput::make('hold_reason')->label('Grund')->maxLength(191),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('update_available', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Plugin')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('active')->label('Aktiv')->boolean(),
                Tables\Columns\TextColumn::make('version')->label('Version'),
                Tables\Columns\TextColumn::make('update_version')
                    ->label('Update auf')
                    ->placeholder('–')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\IconColumn::make('hold')->label('Zurückgehalten')->boolean()
                    ->trueIcon('heroicon-o-pause-circle')->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')->falseColor('gray'),
                Tables\Columns\TextColumn::make('last_seen_at')->label('Gesehen')->since()->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('update_available')->label('Nur mit Update'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Hold'),
            ]);
    }

    // Plugins werden vom Reporter befüllt – kein manuelles Anlegen.
    public function canCreate(): bool
    {
        return false;
    }
}
