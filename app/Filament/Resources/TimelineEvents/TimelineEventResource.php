<?php

namespace App\Filament\Resources\TimelineEvents;

use App\Filament\Resources\TimelineEvents\Pages\CreateTimelineEvent;
use App\Filament\Resources\TimelineEvents\Pages\EditTimelineEvent;
use App\Filament\Resources\TimelineEvents\Pages\ListTimelineEvents;
use App\Filament\Resources\TimelineEvents\Schemas\TimelineEventForm;
use App\Filament\Resources\TimelineEvents\Tables\TimelineEventsTable;
use App\Models\TimelineEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TimelineEventResource extends Resource
{
    protected static ?string $model = TimelineEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TimelineEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimelineEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTimelineEvents::route('/'),
            'create' => CreateTimelineEvent::route('/create'),
            'edit' => EditTimelineEvent::route('/{record}/edit'),
        ];
    }
}
