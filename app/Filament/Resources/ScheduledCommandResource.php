<?php

namespace App\Filament\Resources;

use App\Models\ScheduledCommand;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Cron\CronExpression;
use App\Services\ScheduledCommandService;

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



    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Command Configuration')
                    ->schema([
                        Forms\Components\Select::make('command')
                            ->options(ScheduledCommandService::getAvailableCommands())
                            ->searchable()
                            ->required()
                            ->helperText('Select the artisan command to schedule'),
                        Forms\Components\KeyValue::make('arguments')
                            ->label('Arguments/Options')
                            ->nullable()
                            ->helperText('Add command arguments and options as key-value pairs'),
                    ]),
                Forms\Components\Section::make('Schedule Configuration')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('cron_preset')
                                    ->label('Quick Schedule')
                                    ->options([
                                        '* * * * *' => 'Every minute',
                                        '*/5 * * * *' => 'Every 5 minutes',
                                        '*/15 * * * *' => 'Every 15 minutes',
                                        '*/30 * * * *' => 'Every 30 minutes',
                                        '0 * * * *' => 'Hourly',
                                        '0 */6 * * *' => 'Every 6 hours',
                                        '0 */12 * * *' => 'Every 12 hours',
                                        '0 0 * * *' => 'Daily at midnight',
                                        '0 9 * * *' => 'Daily at 9 AM',
                                        '0 0 * * 0' => 'Weekly (Sunday)',
                                        '0 0 1 * *' => 'Monthly (1st day)',
                                    ])
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('cron_expression', $state))
                                    ->helperText('Select a common schedule or use custom cron expression'),
                                Forms\Components\TextInput::make('cron_expression')
                                    ->required()
                                    ->label('Cron Expression')
                                    ->helperText('Custom cron expression (overrides quick schedule)')
                                    ->rules([
                                        function () {
                                            return function (string $attribute, $value, \Closure $fail) {
                                                try {
                                                    new CronExpression($value);
                                                } catch (\Exception $e) {
                                                    $fail('Invalid cron expression format.');
                                                }
                                            };
                                        },
                                    ]),
                            ]),
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Enable Schedule')
                            ->default(true)
                            ->helperText('Toggle to activate/deactivate this scheduled command'),
                    ]),
                Forms\Components\Section::make('Status & Output')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('status')
                                    ->disabled()
                                    ->default('pending'),
                                Forms\Components\DateTimePicker::make('last_run_at')
                                    ->disabled()
                                    ->label('Last Run'),
                            ]),
                        Forms\Components\Textarea::make('last_output')
                            ->label('Last Output')
                            ->disabled()
                            ->rows(4)
                            ->helperText('Output from the last command execution'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('command')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('cron_expression')
                     ->label('Schedule')
                     ->badge()
                     ->color('gray')
                     ->formatStateUsing(fn (ScheduledCommand $record) => $record->getScheduleDescription())
                     ->tooltip(fn (ScheduledCommand $record) => $record->cron_expression),
                Tables\Columns\ToggleColumn::make('is_enabled')
                    ->label('Active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function (ScheduledCommand $record, $state) {
                        $record->update(['is_enabled' => $state]);
                        Notification::make()
                            ->title($state ? 'Command activated' : 'Command deactivated')
                            ->success()
                            ->send();
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'running' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'pending' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('last_run_at')
                    ->label('Last Run')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                Tables\Columns\TextColumn::make('next_run_at')
                     ->label('Next Run')
                     ->dateTime()
                     ->sortable()
                     ->since()
                     ->placeholder('Not scheduled'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Status')
                    ->placeholder('All commands')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'running' => 'Running',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (ScheduledCommand $record) => $record->is_enabled ? 'Deactivate' : 'Activate')
                    ->icon(fn (ScheduledCommand $record) => $record->is_enabled ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (ScheduledCommand $record) => $record->is_enabled ? 'warning' : 'success')
                    ->action(function (ScheduledCommand $record) {
                        $record->update(['is_enabled' => !$record->is_enabled]);
                        Notification::make()
                            ->title($record->is_enabled ? 'Command activated' : 'Command deactivated')
                            ->success()
                            ->send();
                    })
                    ->iconButton(),
                Tables\Actions\Action::make('run_now')
                    ->label('Force Run')
                    ->icon('heroicon-o-play-circle')
                    ->color('info')
                    ->action(function (ScheduledCommand $record, array $data) {
                         $result = ScheduledCommandService::executeCommand($record, $data['arguments'] ?? []);
                         
                         if ($result['success']) {
                             Notification::make()
                                 ->title('Command executed successfully')
                                 ->body("Exit code: {$result['exit_code']}")
                                 ->success()
                                 ->send();
                         } else {
                             Notification::make()
                                 ->title('Command execution failed')
                                 ->body($result['output'])
                                 ->danger()
                                 ->send();
                         }
                     })
                    ->form([
                        Forms\Components\KeyValue::make('arguments')
                            ->label('Override Arguments/Options')
                            ->nullable()
                            ->helperText('These will be merged with the saved arguments'),
                    ])
                    ->iconButton(),
                Tables\Actions\Action::make('view_output')
                    ->label('View Output')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading('Command Output')
                    ->modalContent(fn (ScheduledCommand $record) => view('filament.modals.command-output', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->visible(fn (ScheduledCommand $record) => !empty($record->last_output))
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                     ->label('Activate Selected')
                     ->icon('heroicon-o-play')
                     ->color('success')
                     ->action(function (Collection $records) {
                         $count = ScheduledCommandService::bulkToggleCommands($records->pluck('id')->toArray(), true);
                         Notification::make()
                             ->title('Commands activated')
                             ->body("Activated {$count} commands")
                             ->success()
                             ->send();
                     }),
                 Tables\Actions\BulkAction::make('deactivate')
                     ->label('Deactivate Selected')
                     ->icon('heroicon-o-pause')
                     ->color('warning')
                     ->action(function (Collection $records) {
                         $count = ScheduledCommandService::bulkToggleCommands($records->pluck('id')->toArray(), false);
                         Notification::make()
                             ->title('Commands deactivated')
                             ->body("Deactivated {$count} commands")
                             ->success()
                             ->send();
                     }),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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