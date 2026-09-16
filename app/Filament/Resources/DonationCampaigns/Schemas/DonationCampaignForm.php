<?php

namespace App\Filament\Resources\DonationCampaigns\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DonationCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('goal_amount')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('raised_amount')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(),
                DatePicker::make('starts_at'),
                DatePicker::make('ends_at'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
