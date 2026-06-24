<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\SitesRelationManager;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Kunden';

    protected static ?string $modelLabel = 'Kunde';

    protected static ?string $pluralModelLabel = 'Kunden';

    protected static ?string $navigationGroup = 'Betrieb';

    protected static ?int $navigationSort = 3;

    // Kunden laufen über die neue Kunden-Konsole (KundenConsole); die CRUD-Resource
    // bleibt erreichbar (Bearbeiten/Anlegen), erscheint aber nicht doppelt im Menü.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Name')->required(),
            Forms\Components\TextInput::make('company')->label('Firma'),
            Forms\Components\TextInput::make('email')->label('E-Mail')->email(),
            Forms\Components\TextInput::make('phone')->label('Telefon')->tel(),
            Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
            Forms\Components\Textarea::make('notes')->label('Notizen')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('company')->label('Firma')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('sites_count')->label('Sites')->counts('sites')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktiv')->boolean(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SitesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view'   => Pages\ViewCustomer::route('/{record}'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
