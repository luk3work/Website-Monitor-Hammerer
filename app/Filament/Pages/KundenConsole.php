<?php

namespace App\Filament\Pages;

use App\Enums\SiteStatus;
use App\Models\Customer;
use App\Models\Site;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Kunden-Konsole: 3-Spalten-Master-Detail (Kundenliste → Detail mit Website-
 * Auswahl & Tabs). Eigene Livewire-Seite — rendert zuverlässig, volle Kontrolle
 * über das Markup. Alle Werte stammen aus echten Daten.
 */
class KundenConsole extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Kunden';

    protected static ?string $title = 'Kunden';

    protected static ?string $navigationGroup = 'Betrieb';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'kunden';

    protected static string $view = 'filament.pages.kunden-console';

    public ?int $customerId = null;

    public ?int $siteId = null;

    public string $tab = 'overview';

    public string $search = '';

    public string $filter = 'all';

    public function mount(): void
    {
        $first = Customer::query()->orderBy('name')->first();
        if ($first) {
            $this->selectCustomer($first->id);
        }
    }

    public function selectCustomer(int $id): void
    {
        $this->customerId = $id;
        $this->tab = 'overview';
        $this->siteId = Site::query()->where('customer_id', $id)->where('is_archived', false)
            ->orderBy('label')->value('id');
    }

    public function selectSite(int $id): void
    {
        $this->siteId = $id;
        $this->tab = 'overview';
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    /* ----------------------------- Daten ---------------------------------- */

    /** Kundenliste (gefiltert/such), mit Sites vorab geladen. */
    public function customersList(): Collection
    {
        return Customer::query()
            ->with(['sites' => fn ($q) => $q->where('is_archived', false)->with('licenses')])
            ->when($this->search !== '', fn ($q) => $q->where(function ($w) {
                $w->where('name', 'like', "%{$this->search}%")
                    ->orWhere('company', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->get()
            ->filter(function (Customer $c) {
                if ($this->filter === 'problems') {
                    return $this->customerIssues($c)->isNotEmpty();
                }
                if ($this->filter === 'critical') {
                    return $this->customerWorst($c) === 'crit';
                }

                return true;
            })
            ->values();
    }

    public function currentCustomer(): ?Customer
    {
        if (! $this->customerId) {
            return null;
        }

        return Customer::query()
            ->with(['sites' => fn ($q) => $q->where('is_archived', false)->orderBy('label')])
            ->find($this->customerId);
    }

    public function currentSite(): ?Site
    {
        if (! $this->siteId) {
            return null;
        }

        return Site::query()->with(['plugins', 'licenses', 'packages'])->find($this->siteId);
    }

    /** Handlungs-Hinweise einer Site aus echten Feldern. */
    public function siteIssues(Site $s): Collection
    {
        $out = [];
        $sslDays = $s->ssl_expires_at ? (int) round(now()->diffInDays($s->ssl_expires_at, false)) : null;
        $domDays = $s->domain_expires_at ? (int) round(now()->diffInDays($s->domain_expires_at, false)) : null;

        if ($s->status === SiteStatus::Offline) {
            $out[] = ['crit', 'Website offline'];
        }
        if ($sslDays !== null && $sslDays <= 7) {
            $out[] = ['crit', "SSL läuft in {$sslDays} Tagen ab"];
        } elseif ($sslDays !== null && $sslDays <= 30) {
            $out[] = ['warn', "SSL läuft in {$sslDays} Tagen ab"];
        }
        if ($domDays !== null && $domDays <= 30) {
            $out[] = ['warn', "Domain läuft in {$domDays} Tagen ab"];
        }
        if ((int) $s->pending_updates >= 6) {
            $out[] = ['warn', "{$s->pending_updates} Updates offen"];
        }
        if ($s->status === SiteStatus::Maintenance) {
            $out[] = ['info', 'Wartungsmodus aktiv'];
        }
        foreach ($s->licenses ?? [] as $l) {
            if ($l->expires_at) {
                $d = (int) round(now()->diffInDays($l->expires_at, false));
                if ($d <= 21) {
                    $out[] = ['warn', "Lizenz „{$l->name}\" läuft in {$d} Tagen ab"];
                }
            }
        }

        return collect($out);
    }

    public function customerIssues(Customer $c): Collection
    {
        return collect($c->sites)->flatMap(fn (Site $s) => $this->siteIssues($s));
    }

    public function customerWorst(Customer $c): string
    {
        $i = $this->customerIssues($c);
        if ($i->contains(fn ($x) => $x[0] === 'crit')) {
            return 'crit';
        }
        if ($i->contains(fn ($x) => $x[0] === 'warn')) {
            return 'warn';
        }
        if ($i->isNotEmpty()) {
            return 'info';
        }

        return 'ok';
    }

    /** Tage bis Datum (null = unbekannt). */
    public function daysUntil($date): ?int
    {
        return $date ? (int) round(now()->diffInDays($date, false)) : null;
    }
}
