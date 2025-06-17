# Scheduled Commands Management

This system provides comprehensive management of scheduled commands through a Filament resource interface with advanced features for scheduling, monitoring, and executing Laravel Artisan commands.

## Features

### 📋 Command Management
- **Browse & Schedule**: View all available Artisan commands and schedule them with cron expressions
- **Quick Presets**: Common scheduling patterns (every minute, hourly, daily, weekly, monthly)
- **Custom Cron**: Full cron expression support with validation
- **Arguments & Options**: Configure command arguments and options as key-value pairs

### 🎛️ Control & Monitoring
- **Activate/Deactivate**: Toggle commands on/off individually or in bulk
- **Force Run**: Execute commands immediately regardless of schedule
- **Status Tracking**: Monitor command execution status (pending, running, completed, failed)
- **Output Viewing**: View command output in a formatted modal
- **Next Run Calculation**: Automatic calculation of next execution time

### 📊 Dashboard Integration
- **Statistics Widget**: Overview of total, active, and due commands
- **Success Rate Monitoring**: Track command execution success rates
- **Real-time Updates**: Auto-refreshing statistics every 30 seconds

## Usage

### Accessing the Interface

1. Navigate to the Filament admin panel
2. Go to **Scheduled Commands** in the sidebar
3. Use the interface to manage your scheduled commands

### Creating a Scheduled Command

1. Click **New Scheduled Command**
2. **Command Configuration**:
   - Select an Artisan command from the dropdown
   - Add any required arguments/options
3. **Schedule Configuration**:
   - Choose a quick preset or enter a custom cron expression
   - Enable/disable the schedule
4. Save the command

### Managing Commands

#### Individual Actions
- **Edit**: Modify command settings
- **Activate/Deactivate**: Toggle command status
- **Force Run**: Execute immediately with optional argument overrides
- **View Output**: See the last execution output

#### Bulk Actions
- **Activate Selected**: Enable multiple commands at once
- **Deactivate Selected**: Disable multiple commands at once
- **Delete Selected**: Remove multiple commands

### Filtering & Searching

- **Status Filter**: Show only active/inactive commands
- **Execution Status**: Filter by pending/running/completed/failed
- **Search**: Find commands by name
- **Sort**: Order by command name, last run, next run, etc.

## Command Line Integration

### Running Scheduled Commands

Use the custom Artisan command to execute scheduled commands:

```bash
# Run commands that are due according to their schedule
php artisan schedule:run-commands

# Force run all enabled commands regardless of schedule
php artisan schedule:run-commands --force
```

### Laravel Task Scheduler Integration

Add this to your `app/Console/Kernel.php` to integrate with Laravel's scheduler:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('schedule:run-commands')
             ->everyMinute()
             ->withoutOverlapping();
}
```

Then ensure your system cron is configured:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## API Usage

### ScheduledCommandService

The `ScheduledCommandService` provides programmatic access to command management:

```php
use App\Services\ScheduledCommandService;

// Get all available commands
$commands = ScheduledCommandService::getAvailableCommands();

// Create a new scheduled command
$scheduledCommand = ScheduledCommandService::createScheduledCommand([
    'command' => 'app:my-command',
    'arguments' => ['--option' => 'value'],
    'cron_expression' => '0 0 * * *',
    'is_enabled' => true,
]);

// Execute a command immediately
$result = ScheduledCommandService::executeCommand($scheduledCommand);

// Get commands due to run
$dueCommands = ScheduledCommandService::getDueCommands();

// Get statistics
$stats = ScheduledCommandService::getStatistics();
```

### Model Methods

The `ScheduledCommand` model includes helpful methods:

```php
$command = ScheduledCommand::find(1);

// Check if command should run now
if ($command->shouldRun()) {
    // Execute the command
}

// Get human-readable schedule description
echo $command->getScheduleDescription(); // "Daily at midnight"

// Access computed next run time
echo $command->next_run_at; // Carbon instance
```

## Cron Expression Examples

| Expression | Description |
|------------|-------------|
| `* * * * *` | Every minute |
| `*/5 * * * *` | Every 5 minutes |
| `0 * * * *` | Every hour |
| `0 0 * * *` | Daily at midnight |
| `0 9 * * *` | Daily at 9 AM |
| `0 0 * * 0` | Weekly on Sunday |
| `0 0 1 * *` | Monthly on the 1st |
| `0 0 1 1 *` | Yearly on January 1st |

## Security Considerations

- Commands are executed with the same permissions as the web server
- Sensitive commands are automatically excluded from the available list
- Command arguments are validated and sanitized
- Execution logs are maintained for audit purposes

## Troubleshooting

### Common Issues

1. **Commands not executing**:
   - Check if the command is enabled
   - Verify the cron expression is valid
   - Ensure Laravel's task scheduler is running

2. **Invalid cron expression**:
   - Use the validation in the form
   - Test expressions at [crontab.guru](https://crontab.guru/)

3. **Command failures**:
   - Check the output in the "View Output" modal
   - Review Laravel logs for detailed error information
   - Verify command arguments are correct

### Monitoring

- Use the dashboard widget to monitor overall system health
- Check individual command outputs for specific issues
- Monitor Laravel logs for detailed execution information
- Set up alerts for failed command executions if needed

## Advanced Configuration

### Custom Command Discovery

The system automatically discovers commands from:
- Laravel's built-in Artisan commands
- Custom commands in `app/Console/Commands/`
- Package-provided commands

Certain system commands are automatically excluded for security.

### Extending the System

You can extend the functionality by:
- Adding custom validation rules for cron expressions
- Implementing command-specific argument validation
- Adding notification channels for command failures
- Creating custom widgets for specific command monitoring

---

*This documentation covers the enhanced scheduled command management system. For Laravel-specific scheduling documentation, refer to the [official Laravel documentation](https://laravel.com/docs/scheduling).*