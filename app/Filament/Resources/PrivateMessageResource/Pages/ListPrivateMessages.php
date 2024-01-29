<?php

namespace App\Filament\Resources\PrivateMessageResource\Pages;

use App\Filament\Resources\PrivateMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrivateMessages extends ListRecords
{
    protected static string $resource = PrivateMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
