<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\DemoData;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Zentrale Einstellungsseite (nur Admins). Steuert Schwellenwerte, Benachrichtigungen
 * und KI – die Werte werden tatsächlich von der App gelesen (z. B. SiteStatusEvaluator).
 */
class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Verwaltung';

    protected static ?int $navigationSort = 100;

    protected static ?string $navigationLabel = 'Einstellungen';

    protected static ?string $title = 'Einstellungen';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'offline_after_hours' => (int) Setting::get('offline_after_hours', 26),
            'ssl_warn_days'       => (int) Setting::get('ssl_warn_days', 21),
            'domain_warn_days'    => (int) Setting::get('domain_warn_days', 30),
            'license_warn_days'   => (int) Setting::get('license_warn_days', 30),
            'notify_email'        => Setting::get('notify_email', auth()->user()?->email),
            'critical_immediate'  => (bool) Setting::get('critical_immediate', true),
            'digest_interval'     => Setting::get('digest_interval', 'weekly'),
            'ai_provider'         => Setting::get('ai_provider', ''),
            'ai_eu_only'          => (bool) Setting::get('ai_eu_only', true),
            'demo_data_enabled'   => (bool) Setting::get('demo_data_enabled', false),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()->tabs([
                    Tabs\Tab::make('Schwellenwerte')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Section::make('Status & Warnfristen')
                                ->description('Ab wann gilt eine Site als offline, und wie früh wird vor Abläufen gewarnt.')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('offline_after_hours')->label('Offline nach (Stunden)')->numeric()->minValue(1)->required(),
                                    TextInput::make('ssl_warn_days')->label('SSL-Warnung (Tage vorher)')->numeric()->minValue(1)->required(),
                                    TextInput::make('domain_warn_days')->label('Domain-Warnung (Tage vorher)')->numeric()->minValue(1)->required(),
                                    TextInput::make('license_warn_days')->label('Lizenz-Warnung (Tage vorher)')->numeric()->minValue(1)->required(),
                                ]),
                        ]),

                    Tabs\Tab::make('Benachrichtigungen')
                        ->icon('heroicon-o-bell-alert')
                        ->schema([
                            Section::make('E-Mail-Berichte')
                                ->description('Kritisches sofort, Updates/Warnungen gebündelt im Intervall.')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('notify_email')->label('Empfänger-E-Mail')->email()->columnSpanFull(),
                                    Toggle::make('critical_immediate')->label('Kritische Ereignisse sofort senden')->inline(false),
                                    Select::make('digest_interval')->label('Digest-Intervall')
                                        ->options(['off' => 'Aus', 'daily' => 'Täglich', 'weekly' => 'Wöchentlich'])
                                        ->native(false),
                                ]),
                        ]),

                    Tabs\Tab::make('KI')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Section::make('KI-Layer (assistierend)')
                                ->description('Provider mit EU-Verarbeitung & AVV. KI liefert nur Vorschläge – Freigabe durch Menschen.')
                                ->columns(2)
                                ->schema([
                                    Select::make('ai_provider')->label('Provider')
                                        ->options([
                                            ''             => '— noch nicht gewählt —',
                                            'azure_openai' => 'Azure OpenAI (EU-Region)',
                                            'aws_bedrock'  => 'AWS Bedrock (EU-Region)',
                                            'mistral'      => 'Mistral (EU)',
                                            'aleph_alpha'  => 'Aleph Alpha (DE)',
                                        ])
                                        ->native(false),
                                    Toggle::make('ai_eu_only')->label('Nur EU-Verarbeitung zulassen')->inline(false),
                                ]),
                        ]),

                    Tabs\Tab::make('Demo-Daten')
                        ->icon('heroicon-o-beaker')
                        ->schema([
                            Section::make('Beispiel-Daten anzeigen')
                                ->description('Erzeugt vier Beispiel-Sites zum Ausprobieren der Oberfläche. Häkchen entfernen löscht sie wieder restlos.')
                                ->schema([
                                    Toggle::make('demo_data_enabled')
                                        ->label('Demo-Daten anzeigen')
                                        ->helperText('Nur zum Testen – keine echten Kundendaten.')
                                        ->inline(false),
                                ]),
                        ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            Setting::set($key, $value);
        }

        // Demo-Daten anhand des Schalters abgleichen (idempotent).
        if (! empty($state['demo_data_enabled'])) {
            DemoData::enable();
        } else {
            DemoData::disable();
        }

        Notification::make()->success()->title('Einstellungen gespeichert')->send();
    }
}
