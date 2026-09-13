<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\FcmClient;
use App\Services\PushNotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SendPushNotification extends Page
{
    protected static ?string $navigationLabel = 'Push göndər';

    protected static ?string $title = 'Push bildiriş göndər';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'audience' => 'all',
            'title' => '',
            'body' => '',
            'user_ids' => [],
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mesaj')
                ->description('Başlıq və mətn telefonda bildiriş kimi görünür.')
                ->schema([
                    TextInput::make('title')
                        ->label('Başlıq')
                        ->required()
                        ->maxLength(80)
                        ->placeholder('My Sancho'),
                    Textarea::make('body')
                        ->label('Mətn')
                        ->required()
                        ->rows(4)
                        ->maxLength(500)
                        ->placeholder('Qısa elan və ya xəbər…'),
                ]),
            Section::make('Alıcılar')
                ->description('Hamıya, rol üzrə və ya checkbox ilə seçilmiş istifadəçilərə göndərin. Yalnız tokeni olan cihazlara çatır.')
                ->schema([
                    Radio::make('audience')
                        ->label('Kimə')
                        ->options([
                            'all' => 'Bütün istifadəçilər',
                            'clients' => 'Yalnız ailə (client)',
                            'providers' => 'Yalnız xidmətçilər (provider)',
                            'selected' => 'Seçilmiş istifadəçilər',
                        ])
                        ->required()
                        ->live()
                        ->descriptions([
                            'all' => 'Aktiv hesablar (bloklanmışlar istisna).',
                            'selected' => 'Aşağıdakı siyahıdan checkbox ilə seçin.',
                        ]),
                    CheckboxList::make('user_ids')
                        ->label('İstifadəçilər (cihaz tokeni olanlar)')
                        ->options(fn (): array => $this->userCheckboxOptions())
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(1)
                        ->visible(fn (Get $get): bool => $get('audience') === 'selected')
                        ->required(fn (Get $get): bool => $get('audience') === 'selected')
                        ->helperText('Siyahıda ən çox 400 nəfər (tokeni olan). Axtarış adı/telefon üzrə işləyir.'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('send')
                ->footer([
                    Actions::make([
                        Action::make('send')
                            ->label('Push göndər')
                            ->icon(Heroicon::OutlinedPaperAirplane)
                            ->color('primary')
                            ->requiresConfirmation()
                            ->modalHeading('Push göndərilsin?')
                            ->modalDescription('Seçilmiş auditoriyaya FCM bildirişi göndəriləcək.')
                            ->submit('send'),
                    ]),
                ]),
        ]);
    }

    public function send(PushNotificationService $push, FcmClient $fcm, ActivityLogger $logger): void
    {
        $state = $this->form->getState();
        $title = trim((string) ($state['title'] ?? ''));
        $body = trim((string) ($state['body'] ?? ''));
        $audience = (string) ($state['audience'] ?? 'all');

        if ($title === '' || $body === '') {
            Notification::make()
                ->title('Başlıq və mətn vacibdir')
                ->danger()
                ->send();

            return;
        }

        if (! $fcm->isConfigured()) {
            Notification::make()
                ->title('FCM konfiqurasiya olunmayıb')
                ->body('FCM_CREDENTIALS və ya FCM_PROJECT_ID / FCM_CLIENT_EMAIL / FCM_PRIVATE_KEY yoxlayın.')
                ->danger()
                ->send();

            return;
        }

        if (! config('homeservice.feature_push', true)) {
            Notification::make()
                ->title('Push söndürülüb')
                ->body('homeservice.feature_push aktiv deyil.')
                ->warning()
                ->send();

            return;
        }

        $users = $this->resolveRecipients($audience, $state['user_ids'] ?? []);
        if ($users->isEmpty()) {
            Notification::make()
                ->title('Alıcı tapılmadı')
                ->body('Seçilmiş auditoriyada istifadəçi yoxdur.')
                ->warning()
                ->send();

            return;
        }

        $stats = $push->broadcast(
            $users,
            $title,
            $body,
            ['type' => 'admin'],
            auth('admin')->id(),
            $audience,
        );

        $logger->record(
            null,
            'admin.push_broadcast',
            'Admin push göndərildi',
            [
                'audience' => $audience,
                'title' => $title,
                'targeted' => $stats['targeted'],
                'delivered' => $stats['delivered'],
                'skipped_no_token' => $stats['skipped_no_token'],
                'failed' => $stats['failed'] ?? 0,
                'dispatch_id' => $stats['dispatch_id'] ?? null,
                'user_ids' => $audience === 'selected'
                    ? array_values(array_map('intval', (array) ($state['user_ids'] ?? [])))
                    : null,
            ],
            'admin',
        );

        Notification::make()
            ->title('Push göndərildi')
            ->body(
                "Hədəf: {$stats['targeted']} · Çatdı: {$stats['delivered']}"
                .($stats['skipped_no_token'] > 0
                    ? " · Tokensuz: {$stats['skipped_no_token']}"
                    : '')
                .(($stats['failed'] ?? 0) > 0
                    ? " · Uğursuz: {$stats['failed']}"
                    : '')
            )
            ->success()
            ->send();
    }

    /**
     * @param  list<int|string>|mixed  $userIds
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function resolveRecipients(string $audience, mixed $userIds)
    {
        $query = User::query()
            ->where(function (Builder $q): void {
                $q->whereNull('status')->orWhere('status', '!=', 'blocked');
            });

        return match ($audience) {
            'clients' => $query->where('active_role', 'client')->orderBy('id')->get(),
            'providers' => $query->where('active_role', 'provider')->orderBy('id')->get(),
            'selected' => $query
                ->whereIn('id', collect($userIds)->map(fn ($id) => (int) $id)->filter()->all())
                ->orderBy('id')
                ->get(),
            default => $query->orderBy('id')->get(),
        };
    }

    /**
     * @return array<int, string>
     */
    private function userCheckboxOptions(): array
    {
        return User::query()
            ->whereHas('deviceTokens')
            ->where(function (Builder $q): void {
                $q->whereNull('status')->orWhere('status', '!=', 'blocked');
            })
            ->orderByDesc('id')
            ->limit(400)
            ->get(['id', 'name', 'phone', 'active_role'])
            ->mapWithKeys(function (User $user): array {
                $role = $user->active_role === 'provider'
                    ? 'xidmətçi'
                    : ($user->active_role === 'client' ? 'ailə' : '—');
                $label = trim('#'.$user->id.' · '.($user->name ?: 'Adsız').' · '.($user->phone ?: '').' · '.$role);

                return [$user->id => $label];
            })
            ->all();
    }
}
