<?php

namespace App\Filament\Resources\TimelineEvents\Pages;

use App\Filament\Resources\TimelineEvents\TimelineEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTimelineEvent extends CreateRecord
{
    protected static string $resource = TimelineEventResource::class;
}
