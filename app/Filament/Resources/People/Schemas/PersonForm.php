<?php

namespace App\Filament\Resources\People\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PersonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('role'),
                TextInput::make('institution'),
                TextInput::make('expertise'),
                Textarea::make('biography')
                    ->columnSpanFull(),
                TextInput::make('photo'),
                TextInput::make('type')
                    ->required()
                    ->default('scientist'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_published')
                    ->required(),
            ]);
    }
}
