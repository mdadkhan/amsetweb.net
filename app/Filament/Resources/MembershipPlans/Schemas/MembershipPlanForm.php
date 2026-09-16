<?php

namespace App\Filament\Resources\MembershipPlans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MembershipPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->helperText(fn (?string $state, $record) => filled(config('payments.membership_fees.'.($record?->slug)))
                        ? 'A fee is set in the environment for this plan slug and overrides this value at checkout.'
                        : null),
                Select::make('billing_interval')
                    ->options(['monthly' => 'Monthly', 'yearly' => 'Yearly', 'lifetime' => 'Lifetime'])
                    ->required(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
