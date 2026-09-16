<?php

namespace App\Filament\Resources\Members\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('membership_plan_id')
                    ->relationship('membershipPlan', 'name')
                    ->searchable(),
                TextInput::make('membership_number')
                    ->required(),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required(),
                TextInput::make('phone'),
                TextInput::make('organization'),
                TextInput::make('address_line1'),
                TextInput::make('address_line2'),
                TextInput::make('city'),
                TextInput::make('state'),
                TextInput::make('postal_code'),
                TextInput::make('country'),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired', 'cancelled' => 'Cancelled'])
                    ->required(),
                DatePicker::make('joined_at'),
                DatePicker::make('expires_at'),
                DatePicker::make('last_renewed_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
