<?php

namespace App\Filament\Resources\SiteResource\RelationManagers;

use App\Enums\Severity;
use App\Enums\TaskStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Aufgaben';

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')->label('Titel')->required(),
            Textarea::make('description')->label('Beschreibung')->columnSpanFull(),
            Select::make('severity')->label('Schwere')
                ->options(collect(Severity::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                ->default('info')->required(),
            Select::make('status')->label('Status')
                ->options(collect(TaskStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                ->default('open')->required(),
            DatePicker::make('due_date')->label('Frist')->native(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('severity')->label('')->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),
                Tables\Columns\TextColumn::make('title')->label('Aufgabe')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Typ')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),
                Tables\Columns\TextColumn::make('due_date')->label('Frist')->date('d.m.Y')->placeholder('–'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(TaskStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Aufgabe anlegen'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
