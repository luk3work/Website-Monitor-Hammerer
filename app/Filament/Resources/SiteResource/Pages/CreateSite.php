<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSite extends CreateRecord
{
    protected static string $resource = SiteResource::class;

    // Beim Anlegen automatisch ein starkes Secret erzeugen, falls leer.
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['secret'])) {
            $data['secret'] = bin2hex(random_bytes(32));
        }
        $data['secret_rotated_at'] = now();

        return $data;
    }
}
