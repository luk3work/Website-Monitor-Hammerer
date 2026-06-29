<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Smoke-Test: lädt jede Cockpit-Seite und feuert jede Livewire-Aktion,
 * um 500er (column/relation/logic) reproduzierbar aufzudecken.
 */
class CockpitSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::query()->firstOrFail());
    }

    /** /admin (altes Filament-Cockpit) leitet ins neue Cockpit um. */
    public function test_admin_redirects_into_cockpit(): void
    {
        $this->get('/admin')->assertRedirect(route('cockpit.dashboard'));
    }

    /** Gäste werden zur Anmeldung geschickt, nicht mit 403/500 abgewiesen. */
    public function test_guests_are_redirected_to_login(): void
    {
        auth()->logout();
        $this->get(route('cockpit.dashboard'))
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    /** Abmelden läuft über POST (der Logout-Button im Cockpit) und meldet ab. */
    public function test_logout_via_post_works(): void
    {
        $this->post(route('filament.admin.auth.logout'))->assertRedirect();
        $this->assertGuest();
    }

    /** Alle GET-Routen müssen 200 liefern. */
    public function test_all_cockpit_pages_load(): void
    {
        foreach ([
            'cockpit.dashboard', 'cockpit.tasks', 'cockpit.kunden', 'cockpit.seiten',
            'cockpit.domains', 'cockpit.berichte', 'cockpit.benutzer', 'cockpit.einstellungen',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    /** Dashboard rendert die neuen Graphen ohne Fehler. */
    public function test_dashboard_renders_charts(): void
    {
        $this->get(route('cockpit.dashboard'))
            ->assertOk()
            ->assertSee('Status-Verteilung')
            ->assertSee('Aufgaben nach Schweregrad')
            ->assertSee('Meiste Updates');
    }

    public function test_kunden_interactions(): void
    {
        $customer = \App\Models\Customer::query()->whereHas('sites')->firstOrFail();
        $site     = $customer->sites()->firstOrFail();

        $c = Livewire::test(\App\Livewire\Cockpit\Kunden::class)
            ->call('selectCustomer', $customer->id)->assertOk()
            ->call('selectSite', $site->id)->assertOk();

        foreach (['overview', 'plugins', 'domain', 'packages', 'tasks'] as $tab) {
            $c->call('setTab', $tab)->assertOk();
        }

        Livewire::test(\App\Livewire\Cockpit\Kunden::class)
            ->set('search', 'a')->assertOk();
    }

    public function test_tasks_interactions(): void
    {
        $site = \App\Models\Site::query()->firstOrFail();

        Livewire::test(\App\Livewire\Cockpit\Tasks::class)
            ->set('filterStatus', 'open')->assertOk()
            ->set('filterSev', 'critical')->assertOk()
            ->set('search', 'ssl')->assertOk()
            ->call('openModal')->assertOk()
            ->set('newTitle', 'Testaufgabe')
            ->set('newSiteId', $site->id)
            ->set('newSeverity', 'warning')
            ->call('createTask')->assertOk()->assertHasNoErrors();

        $task = \App\Models\Task::query()->firstOrFail();
        Livewire::test(\App\Livewire\Cockpit\Tasks::class)
            ->call('updateStatus', $task->id, 'done')->assertOk()
            ->call('updateStatus', $task->id, 'open')->assertOk();
    }

    public function test_filters_on_list_pages(): void
    {
        Livewire::test(\App\Livewire\Cockpit\Seiten::class)
            ->set('search', 'shop')->assertOk()
            ->set('filterStatus', 'offline')->assertOk()
            ->set('filterSsl', 'crit')->assertOk();

        Livewire::test(\App\Livewire\Cockpit\Domains::class)
            ->set('search', 'at')->assertOk()
            ->set('filterSsl', 'warn')->assertOk()
            ->set('filterDom', 'crit')->assertOk();
    }

    public function test_einstellungen_save(): void
    {
        // Produktionsfall: Werte liegen im {"v": …}-Format vor (alte Filament-Settings).
        // Direkt in die JSON-Spalte -> würde sonst beim Zuweisen an typisierte
        // Properties (string $aiProvider) einen 500 (TypeError) auslösen.
        \App\Models\Setting::query()->insert([
            ['key' => 'ai_provider', 'value' => json_encode(['v' => 'anthropic']), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ssl_warn_days', 'value' => json_encode(['v' => 14]), 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::test(\App\Livewire\Cockpit\Einstellungen::class)
            ->assertOk()
            ->assertSet('aiProvider', 'anthropic')
            ->assertSet('sslWarnDays', 14)
            ->set('sslWarnDays', 21)
            ->set('aiProvider', 'none')
            ->call('save')->assertOk()->assertHasNoErrors();
    }

    /** Edge cases, die echte Produktionsdaten treffen, Demodaten aber evtl. nicht. */
    public function test_pagination_and_null_edge_cases(): void
    {
        // 40 Tasks ohne Site -> erzwingt Seite 2 + Render mit site_id = null
        \App\Models\Task::query()->insert(
            collect(range(1, 40))->map(fn ($i) => [
                'title' => "Bulk #$i", 'type' => 'manual', 'severity' => 'info',
                'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
            ])->all()
        );

        Livewire::test(\App\Livewire\Cockpit\Tasks::class)
            ->assertOk()
            ->call('gotoPage', 2)->assertOk();   // schlägt ohne WithPagination fehl

        Livewire::test(\App\Livewire\Cockpit\Seiten::class)
            ->call('gotoPage', 2)->assertOk();

        // Kunde ganz ohne Sites auswählen
        $empty = \App\Models\Customer::query()->create(['name' => 'Leerkunde', 'is_active' => true]);
        Livewire::test(\App\Livewire\Cockpit\Kunden::class)
            ->call('selectCustomer', $empty->id)->assertOk();
    }
}
