<?php

namespace App\Filament\Resources\TimelineEvents\Pages;

use App\Filament\Resources\TimelineEvents\TimelineEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTimelineEvents extends ListRecords
{
    protected static string $resource = TimelineEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
