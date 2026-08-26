<?php

namespace App\Filament\Pages;

use App\Support\RuntimeSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageSettings extends Page
{
    protected static ?string $navigationLabel = 'Ayarlar';

    protected static ?string $title = 'Ayarlar';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(RuntimeSettings::formState());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $localeOptions = config('app_locales.labels', [
            'az' => 'Azərbaycan',
            'en' => 'English',
            'ru' => 'Русский',
        ]);

        return $schema->components([
            Section::make('Dillər')
                ->description('App-də görünən dillər. Söndürülən dil seçicidən çıxır.')
                ->columns(2)
                ->schema([
                    CheckboxList::make('locales_supported')
                        ->label('Aktiv dillər')
                        ->options($localeOptions)
                        ->required()
                        ->minItems(1)
                        ->live()
                        ->columns(3)
                        ->columnSpanFull(),
                    Select::make('locales_default')
                        ->label('Əsas dil')
                        ->options(fn (Get $get) => collect($localeOptions)
                            ->only($get('locales_supported') ?? [])
                            ->all())
                        ->required(),
                ]),
            Section::make('Balans paketləri')
                ->description('Bir dəfəlik kredit. Abunə deyil. İstifadəçi bu məbləğlərdən birini alır.')
                ->schema([
                    TextInput::make('welcome_bonus')
                        ->label('Xoşgəldin bonusu (AZN)')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    Repeater::make('wallet_packages')
                        ->label('Paketlər (AZN)')
                        ->simple(
                            TextInput::make('amount')
                                ->numeric()
                                ->minValue(1)
                                ->required(),
                        )
                        ->minItems(1)
                        ->maxItems(8)
                        ->required()
                        ->addActionLabel('Paket əlavə et'),
                ]),
            Section::make('CONNECT')
                ->columns(2)
                ->schema([
                    TextInput::make('connect_free_quota')
                        ->label('Pulsuz CONNECT sayı')
                        ->integer()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('connect_free_days')
                        ->label('Pulsuz gün (yeni hesab)')
                        ->integer()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('connect_daily_limit')
                        ->label('Gündəlik limit')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('connect_fee')
                        ->label('Haqq (AZN)')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                ]),
            Section::make('Təcili (Urgent)')
                ->columns(2)
                ->schema([
                    TextInput::make('urgent_fee')
                        ->label('Haqq (AZN)')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('urgent_hours')
                        ->label('Müddət (saat)')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('urgent_daily_limit')
                        ->label('Gündəlik limit')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('urgent_radius_km')
                        ->label('Radius (km)')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                ]),
            Section::make('Bump')
                ->columns(2)
                ->schema([
                    TextInput::make('bump_up_fee')
                        ->label('Haqq (AZN)')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('bump_hours')
                        ->label('Müddət (saat)')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('bump_daily_limit')
                        ->label('Gündəlik limit')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('bump_boost_km')
                        ->label('Vurğu (km ekvivalent)')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Axtarışda təxminən bu qədər km yaxın sayılır. Siyahının həmişə birincisi olmur.')
                        ->required(),
                ]),
            Section::make('VIP və verified')
                ->columns(2)
                ->schema([
                    TextInput::make('vip_fee')
                        ->label('VIP haqq (AZN)')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    TextInput::make('vip_days')
                        ->label('VIP müddət (gün)')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('verified_fee')
                        ->label('Verified haqq (AZN)')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                ]),
            Section::make('Axtarış')
                ->columns(2)
                ->schema([
                    TextInput::make('search_radius_km')
                        ->label('Axtarış radiusu (km)')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('max_category_tags')
                        ->label('Maks. kateqoriya tag')
                        ->integer()
                        ->minValue(1)
                        ->maxValue(10)
                        ->required(),
                ]),
            Section::make('Funksiyalar')
                ->description('Söndürülən funksiya app-də gizlənir və ya işləmir. Xəritə/push üçün açarlar yenə .env-də qalır.')
                ->schema([
                    Toggle::make('feature_voice_search')
                        ->label('Səsli axtarış'),
                    Toggle::make('feature_maps')
                        ->label('Xəritə'),
                    Toggle::make('feature_push')
                        ->label('Push bildiriş (FCM açarı varsa)'),
                ]),
            Section::make('OTP / SMS')
                ->description('Kodun özü yerli SMS gateway-dən gələcək (.env). Burada limitlər.')
                ->columns(2)
                ->schema([
                    TextInput::make('otp_ttl_minutes')
                        ->label('Kodun ömrü (dəq)')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('otp_max_attempts')
                        ->label('Maks. yoxlama cəhdi')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('otp_send_max')
                        ->label('Pəncərədə maks. göndərmə')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('otp_send_window_minutes')
                        ->label('Göndərmə pəncərəsi (dəq)')
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('otp_resend_seconds')
                        ->label('Yenidən göndərmə (san)')
                        ->integer()
                        ->minValue(5)
                        ->required(),
                    Toggle::make('otp_allow_debug_code')
                        ->label('Lokal debug kodu (123456)')
                        ->helperText('Production-da sönük saxlayın.'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Yadda saxla')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        RuntimeSettings::save($this->form->getState());
        $this->form->fill(RuntimeSettings::formState());

        Notification::make()
            ->title('Ayarlar yadda saxlandı')
            ->body('App növbəti açılışda / bootstrap-da yeni limitləri götürəcək.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetDefaults')
                ->label('Standarta qaytar')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Standart dəyərlərə qayıt?')
                ->modalDescription('Admin-də saxlanan bütün ayarlar silinir. .env / config default-ları qayıdır.')
                ->action(function (): void {
                    RuntimeSettings::resetToDefaults();
                    $this->form->fill(RuntimeSettings::formState());
                    Notification::make()
                        ->title('Standart ayarlar bərpa olundu')
                        ->success()
                        ->send();
                }),
        ];
    }
}
