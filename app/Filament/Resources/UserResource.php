<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Settings\MailSettings;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Auth\VerifyEmail;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static int $globalSearchResultsLimit = 20;

    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        /** @var User $user */
        $user = Auth::user();

        return $form->schema([
            Card::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('company_id')
                                ->relationship('company', 'name')
                                ->required()
                                ->visible(fn () => $user->isSuperAdmin())
                                ->searchable()
                                ->preload(),

                            TextInput::make('username')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255),

                            TextInput::make('firstname')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('lastname')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('password')
                                ->password()
                                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                ->dehydrated(fn ($state) => filled($state))
                                ->required(fn (string $context): bool => $context === 'create'),

                            Select::make('roles')
                                ->multiple()
                                ->relationship('roles', 'name')
                                ->preload()
                                ->searchable(),
                        ]),
                ])
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        /** @var User $user */
        $user = Auth::user();

        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: !$user->isSuperAdmin()),

                TextColumn::make('username')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('firstname')
                    ->searchable(),

                TextColumn::make('lastname')
                    ->searchable(),

                TextColumn::make('roles.name')
                    ->badge(),

                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->visible(fn () => $user->isSuperAdmin()),

                SelectFilter::make('roles')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->email;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email', 'firstname', 'lastname'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'name' => $record->firstname . ' ' . $record->lastname,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __("menu.nav_group.access");
    }

    public static function doResendEmailVerification($settings = null, $user): void
    {
        if (!method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

        if ($settings->isMailSettingsConfigured()) {
            $notification = new VerifyEmail();
            $notification->url = Filament::getVerifyEmailUrl($user);

            $settings->loadMailSettingsToConfig();

            $user->notify($notification);


            Notification::make()
                ->title(__('resource.user.notifications.verify_sent.title'))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('resource.user.notifications.verify_warning.title'))
                ->body(__('resource.user.notifications.verify_warning.description'))
                ->warning()
                ->send();
        }
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User $user */
        $user = Auth::user();

        if (!$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }
}
