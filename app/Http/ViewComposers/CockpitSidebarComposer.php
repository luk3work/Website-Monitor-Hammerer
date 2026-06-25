<?php

namespace App\Http\ViewComposers;

use App\Models\Site;
use App\Models\Task;
use Illuminate\View\View;

class CockpitSidebarComposer
{
    public function compose(View $view): void
    {
        $criticalCount = Task::query()
            ->whereIn('status', ['open', 'in_progress', 'blocked'])
            ->where('severity', 'critical')
            ->count();

        $openTaskCount = Task::query()
            ->whereIn('status', ['open', 'in_progress', 'blocked'])
            ->count();

        $problemSites = Site::query()
            ->where('is_archived', false)
            ->whereIn('status', ['offline'])
            ->count();

        $domainAlerts = Site::query()
            ->where('is_archived', false)
            ->where(function ($q) {
                $q->where(fn ($q) => $q->whereNotNull('ssl_expires_at')->whereDate('ssl_expires_at', '<=', now()->addDays(30)))
                  ->orWhere(fn ($q) => $q->whereNotNull('domain_expires_at')->whereDate('domain_expires_at', '<=', now()->addDays(60)));
            })
            ->count();

        $view->with(compact('criticalCount', 'openTaskCount', 'problemSites', 'domainAlerts'));
    }
}
