<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\SiteStatusEvaluator;
use Illuminate\Console\Command;

/**
 * Täglicher Ablauf-Scan: SSL-, Domain- und Lizenz-Fristen.
 * Die eigentliche Aufgaben-Logik liegt im SiteStatusEvaluator (eine Quelle der Wahrheit);
 * dieser Command stößt die Neubewertung nur planmäßig an.
 */
class SitesExpiryScan extends Command
{
    protected $signature = 'sites:expiry-scan';

    protected $description = 'SSL/Domain/Lizenz-Abläufe prüfen und fällige Aufgaben erzeugen';

    public function handle(SiteStatusEvaluator $evaluator): int
    {
        $count = 0;

        Site::query()
            ->where('is_archived', false)
            ->with('licenses')
            ->chunkById(200, function ($sites) use ($evaluator, &$count) {
                foreach ($sites as $site) {
                    $evaluator->evaluate($site);
                    $count++;
                }
            });

        $this->info("Ablauf-Scan: {$count} Site(s) geprüft.");

        return self::SUCCESS;
    }
}
