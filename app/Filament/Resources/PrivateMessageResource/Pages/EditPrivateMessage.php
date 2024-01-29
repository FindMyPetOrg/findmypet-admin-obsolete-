<?php

namespace App\Filament\Resources\PrivateMessageResource\Pages;

use App\Filament\Resources\PrivateMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrivateMessage extends EditRecord
{
    protected static string $resource = PrivateMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
