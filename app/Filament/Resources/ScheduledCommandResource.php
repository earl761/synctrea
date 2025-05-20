<?php

namespace App\Filament\Resources;

use App\Models\ScheduledCommand;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class ScheduledCommandResource extends Resource
{
    protected static ?string $model = ScheduledCommand::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Command Scheduler';
    protected static ?int $navigationSort = 100;

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    }

    public static function getAvailableCommands(): array
    {
        $commands = [];
        foreach (glob(app_path('Console/Commands/*.php')) as $file) {
            $contents = file_get_contents($file);
            if (preg_match("/protected \$signature\s*=\s*'([^']+)'/", $contents, $matches)) {
                $signature = trim($matches[1]);
                $commands[$signature] = $signature;
            }
        }
        return $commands;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('command')
                    ->options(self::getAvailableCommands())
                    ->searchable()
                    ->required(),
                Forms\Components\KeyValue::make('arguments')->label('Arguments/Options')->nullable(),
                Forms\Components\TextInput::make('cron_expression')->required()->label('Cron Expression'),
                Forms\Components\Toggle::make('is_enabled')->label('Enabled'),
                Forms\Components\Textarea::make('last_output')->label('Last Output')->disabled(),
                Forms\Components\TextInput::make('status')->disabled(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('command')->searchable(),
                Tables\Columns\TextColumn::make('cron_expression'),
                Tables\Columns\IconColumn::make('is_enabled')->boolean(),
                Tables\Columns\TextColumn::make('last_run_at')->dateTime(),
                Tables\Columns\TextColumn::make('next_run_at')->dateTime(),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('run_now')
                    ->label('Run Now')
                    ->action(function (ScheduledCommand $record, array $data) {
                        $arguments = $record->arguments ?? [];
                        $output = Artisan::call($record->command, $arguments);
                        $record->last_run_at = now();
                        $record->last_output = Artisan::output();
                        $record->status = 'completed';
                        $record->save();
                    })
                    ->form([
                        Forms\Components\KeyValue::make('arguments')->label('Arguments/Options')->nullable(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ScheduledCommandResource\Pages\ListScheduledCommands::route('/'),
            'create' => ScheduledCommandResource\Pages\CreateScheduledCommand::route('/create'),
            'edit' => ScheduledCommandResource\Pages\EditScheduledCommand::route('/{record}/edit'),
        ];
    }
} 