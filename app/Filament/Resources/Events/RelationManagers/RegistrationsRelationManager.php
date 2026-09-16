<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->options(['pending' => 'Pending', 'registered' => 'Registered', 'checked_in' => 'Checked in', 'cancelled' => 'Cancelled'])
                    ->required(),
                DateTimePicker::make('checked_in_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('confirmation_code'),
                TextColumn::make('name'),
                TextColumn::make('email'),
                TextColumn::make('quantity')
                    ->numeric(),
                TextColumn::make('amount_due')
                    ->money('usd'),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
