<?php

namespace App\Livewire\Cockpit;

use App\Models\Customer;
use App\Models\License;
use App\Models\Site;
use App\Models\Task;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Dashboard extends Component
{
    public function render()
    {
        $sites    = Site::query()->where('is_archived', false)->with('customer', 'tasks', 'licenses')->get();
        $customers = Customer::query()->with('sites')->get();

        $totalSites     = $sites->count();
        $totalCustomers = $customers->count();
        $critSites      = $sites->where('status', 'offline')->count();
        $warnSites      = $sites->where('status', 'maintenance')->count();
        $sslSoon        = $sites->filter(fn ($s) => $s->ssl_expires_at && $s->ssl_expires_at->diffInDays(now(), false) <= 30 && $s->ssl_expires_at->isFuture())->count();
        $sslCrit        = $sites->filter(fn ($s) => $s->ssl_expires_at && $s->ssl_expires_at->isPast())->count();
        $pendingUpdates = $sites->sum('pending_updates');
        $openTasks      = Task::query()->whereNull('resolved_at')->count();

        // Issues pro Site: kombiniert Status + SSL + Updates
        $issues = collect();
        foreach ($sites as $s) {
            if ($s->status->value === 'offline') {
                $issues->push(['sev' => 'crit', 'ic' => 'ti-plug-connected-x', 't' => 'Website offline', 's' => $s->label, 'href' => route('cockpit.kunden')]);
            }
            if ($s->ssl_expires_at && $s->ssl_expires_at->diffInDays(now(), false) <= 7) {
                $issues->push(['sev' => 'crit', 'ic' => 'ti-lock-exclamation', 't' => 'SSL läuft in '.$s->ssl_expires_at->diffInDays(now(), false).'d ab', 's' => $s->label, 'href' => route('cockpit.domains')]);
            } elseif ($s->ssl_expires_at && $s->ssl_expires_at->diffInDays(now(), false) <= 30) {
                $issues->push(['sev' => 'warn', 'ic' => 'ti-lock', 't' => 'SSL bald fällig', 's' => $s->label, 'href' => route('cockpit.domains')]);
            }
            if ($s->pending_updates >= 6) {
                $issues->push(['sev' => 'warn', 'ic' => 'ti-refresh', 't' => $s->pending_updates.' Plugin-Updates offen', 's' => $s->label, 'href' => route('cockpit.seiten')]);
            }
            if ($s->domain_expires_at && $s->domain_expires_at->diffInDays(now(), false) <= 40) {
                $issues->push(['sev' => 'warn', 'ic' => 'ti-world', 't' => 'Domain läuft ab', 's' => $s->label, 'href' => route('cockpit.domains')]);
            }
        }
        $issues = $issues->sortBy(fn ($i) => ['crit' => 0, 'warn' => 1, 'info' => 2][$i['sev']])->values()->take(8);

        // Ablauf-Timeline
        $expiries = collect();
        foreach ($sites->filter(fn ($s) => $s->ssl_expires_at && $s->ssl_expires_at->diffInDays(now(), false) <= 90) as $s) {
            $d = (int) $s->ssl_expires_at->diffInDays(now(), false);
            $expiries->push(['tag' => 'SSL', 'name' => $s->label, 'days' => $d, 'tone' => $d <= 7 ? 'crit' : ($d <= 30 ? 'warn' : 'ok'), 'href' => route('cockpit.domains')]);
        }
        foreach ($sites->filter(fn ($s) => $s->domain_expires_at && $s->domain_expires_at->diffInDays(now(), false) <= 90) as $s) {
            $d = (int) $s->domain_expires_at->diffInDays(now(), false);
            $expiries->push(['tag' => 'DOM', 'name' => $s->label, 'days' => $d, 'tone' => $d <= 30 ? 'crit' : ($d <= 60 ? 'warn' : 'ok'), 'href' => route('cockpit.domains')]);
        }
        License::query()->whereNotNull('expires_at')->whereDate('expires_at', '<=', now()->addDays(90))->with('site')->get()->each(function ($l) use (&$expiries) {
            $d = (int) now()->diffInDays($l->expires_at, false);
            $expiries->push(['tag' => 'LIZ', 'name' => ($l->product_name ?? 'Lizenz').' · '.($l->site?->label ?? '—'), 'days' => $d, 'tone' => $d <= 14 ? 'crit' : ($d <= 30 ? 'warn' : 'ok'), 'href' => route('cockpit.seiten')]);
        });
        $expiries = $expiries->sortBy('days')->values()->take(6);

        return view('livewire.cockpit.dashboard', compact(
            'totalSites', 'totalCustomers', 'critSites', 'warnSites',
            'sslSoon', 'sslCrit', 'pendingUpdates', 'openTasks',
            'issues', 'expiries'
        ));
    }
}
