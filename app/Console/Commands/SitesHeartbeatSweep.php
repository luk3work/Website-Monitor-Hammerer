<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\SiteStatusEvaluator;
use Illuminate\Console\Command;

/**
 * Dead-Man's-Switch: bewertet den Status aller aktiven Sites neu.
 * Sites, die zu lange nichts gemeldet haben, kippen so auf "offline"
 * und erzeugen eine kritische Aufgabe – ohne dass der Reporter "down"
 * selbst erkennen müsste.
 */
class SitesHeartbeatSweep extends Command
{
    protected $signature = 'sites:heartbeat-sweep';

    protected $description = 'Status aller Sites anhand des letzten Lebenszeichens neu bewerten';

    public function handle(SiteStatusEvaluator $evaluator): int
    {
        $count = 0;

        Site::query()
            ->where('is_archived', false)
            ->chunkById(200, function ($sites) use ($evaluator, &$count) {
                foreach ($sites as $site) {
                    $evaluator->evaluate($site);
                    $count++;
                }
            });

        $this->info("Heartbeat-Sweep: {$count} Site(s) bewertet.");

        return self::SUCCESS;
    }
}
