<?php

namespace App\Livewire\Cockpit;

use App\Models\Site;
use App\Models\Task;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Dashboard extends Component
{
    public function render()
    {
        $sites = Site::query()
            ->with(['customer', 'packages'])
            ->where('is_archived', false)
            ->get();

        $totalSites     = $sites->count();
        $totalCustomers = $sites->pluck('customer_id')->unique()->count();
        $offlineSites   = $sites->whereIn('status', ['offline'])->count();
        $sslCrit        = $sites->filter(fn ($s) => $s->sslDaysLeft() !== null && $s->sslDaysLeft() < 14)->count();
        $sslWarn        = $sites->filter(fn ($s) => $s->sslDaysLeft() !== null && $s->sslDaysLeft() >= 14 && $s->sslDaysLeft() < 30)->count();
        $domCrit        = $sites->filter(fn ($s) => $s->domainDaysLeft() !== null && $s->domainDaysLeft() < 30)->count();
        $openTasks      = Task::query()->whereIn('status', ['open', 'in_progress', 'blocked'])->count();
        $critTasks      = Task::query()->whereIn('status', ['open', 'in_progress', 'blocked'])->where('severity', 'critical')->count();
        $pendingUpdates = $sites->sum('pending_updates');

        $feed = $this->buildFeed($sites);

        $expiries = $sites
            ->filter(fn ($s) => ($s->sslDaysLeft() !== null && $s->sslDaysLeft() < 90)
                             || ($s->domainDaysLeft() !== null && $s->domainDaysLeft() < 90))
            ->sortBy(fn ($s) => min($s->sslDaysLeft() ?? 9999, $s->domainDaysLeft() ?? 9999))
            ->take(8);

        return view('livewire.cockpit.dashboard', compact(
            'totalSites', 'totalCustomers', 'offlineSites',
            'sslCrit', 'sslWarn', 'domCrit', 'openTasks', 'critTasks', 'pendingUpdates',
            'feed', 'expiries'
        ));
    }

    private function buildFeed(Collection $sites): Collection
    {
        $items = collect();

        foreach ($sites as $site) {
            $tier = $this->packageTier($site);

            if ($site->status?->value === 'offline') {
                $items->push(['severity'=>'critical','type'=>'offline','icon'=>'ti-wifi-off',
                    'title'=>"{$site->name} ist offline",'customer'=>$site->customer?->name,
                    'meta'=>'Kein Heartbeat','score'=>0 + $tier,'site_id'=>$site->id]);
            }

            $ssl = $site->sslDaysLeft();
            if ($ssl !== null && $ssl < 14) {
                $items->push(['severity'=>'critical','type'=>'ssl','icon'=>'ti-certificate-off',
                    'title'=>"SSL kritisch: {$site->name}",'customer'=>$site->customer?->name,
                    'meta'=>$ssl <= 0 ? 'Abgelaufen' : "Noch {$ssl}d",'score'=>10 + $tier,'site_id'=>$site->id]);
            } elseif ($ssl !== null && $ssl < 30) {
                $items->push(['severity'=>'warning','type'=>'ssl','icon'=>'ti-certificate',
                    'title'=>"SSL bald ablaufend: {$site->name}",'customer'=>$site->customer?->name,
                    'meta'=>"Noch {$ssl}d",'score'=>20 + $tier,'site_id'=>$site->id]);
            }

            $dom = $site->domainDaysLeft();
            if ($dom !== null && $dom < 30) {
                $items->push(['severity'=>'critical','type'=>'domain','icon'=>'ti-world-off',
                    'title'=>"Domain kritisch: {$site->name}",'customer'=>$site->customer?->name,
                    'meta'=>"Noch {$dom}d",'score'=>12 + $tier,'site_id'=>$site->id]);
            } elseif ($dom !== null && $dom < 60) {
                $items->push(['severity'=>'warning','type'=>'domain','icon'=>'ti-world',
                    'title'=>"Domain bald ablaufend: {$site->name}",'customer'=>$site->customer?->name,
                    'meta'=>"Noch {$dom}d",'score'=>25 + $tier,'site_id'=>$site->id]);
            }

            $upd = $site->pending_updates ?? 0;
            if ($upd > 0) {
                $sev = $upd >= 5 ? 'warning' : 'info';
                $items->push(['severity'=>$sev,'type'=>'updates','icon'=>'ti-refresh-alert',
                    'title'=>"{$upd} Update(s) ausstehend: {$site->name}",'customer'=>$site->customer?->name,
                    'meta'=>"{$upd} Plugins/Core",'score'=>($sev==='warning'?30:40)+$tier,'site_id'=>$site->id]);
            }
        }

        return $items->sortBy('score')->values()->take(20);
    }

    private function packageTier(Site $site): int
    {
        $keys = $site->packages->where('pivot.state', 'booked')->pluck('key')->map(fn($k) => strtolower($k))->toArray();
        foreach (['premium','enterprise','full','komplett'] as $t) {
            if (collect($keys)->contains(fn($k) => str_contains($k, $t))) return 0;
        }
        foreach (['pro','plus','advanced'] as $t) {
            if (collect($keys)->contains(fn($k) => str_contains($k, $t))) return 1;
        }
        return count($keys) > 0 ? 2 : 3;
    }
}
