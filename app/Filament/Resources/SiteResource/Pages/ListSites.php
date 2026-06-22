<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Filament\Widgets\CockpitStatsOverview;
use App\Filament\Widgets\NeedsActionTable;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSites extends ListRecords
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // Das Cockpit (KPIs + Braucht-Handlung) sitzt über der Sites-Liste.
    protected function getHeaderWidgets(): array
    {
        return [
            CockpitStatsOverview::class,
            NeedsActionTable::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
