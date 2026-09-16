<?php

namespace App\Filament\Resources\DonationCampaigns\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DonationsRelationManager extends RelationManager
{
    protected static string $relationship = 'donations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('donor_name')
            ->columns([
                TextColumn::make('donor_name'),
                TextColumn::make('donor_email'),
                TextColumn::make('amount')
                    ->money('usd'),
                TextColumn::make('frequency'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('receipt_number'),
            ]);
    }
}
