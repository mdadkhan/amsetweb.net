<?php

namespace App\Filament\Resources\DonationCampaigns;

use App\Filament\Resources\DonationCampaigns\Pages\CreateDonationCampaign;
use App\Filament\Resources\DonationCampaigns\Pages\EditDonationCampaign;
use App\Filament\Resources\DonationCampaigns\Pages\ListDonationCampaigns;
use App\Filament\Resources\DonationCampaigns\RelationManagers\DonationsRelationManager;
use App\Filament\Resources\DonationCampaigns\Schemas\DonationCampaignForm;
use App\Filament\Resources\DonationCampaigns\Tables\DonationCampaignsTable;
use App\Models\DonationCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DonationCampaignResource extends Resource
{
    protected static ?string $model = DonationCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'Fundraising';

    public static function form(Schema $schema): Schema
    {
        return DonationCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DonationCampaignsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DonationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDonationCampaigns::route('/'),
            'create' => CreateDonationCampaign::route('/create'),
            'edit' => EditDonationCampaign::route('/{record}/edit'),
        ];
    }
}
