<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    // Leeres Secret-Feld bedeutet "behalten"; ein gesetztes Feld rotiert das Secret.
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['secret'])) {
            unset($data['secret']);
        } else {
            $data['secret_rotated_at'] = now();
        }

        return $data;
    }
}
