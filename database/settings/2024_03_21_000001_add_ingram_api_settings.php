<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class AddIngramApiSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.ingram_api_key', '');
        $this->migrator->add('general.ingram_api_secret', '');
    }

    public function down(): void
    {
        $this->migrator->delete('general.ingram_api_key');
        $this->migrator->delete('general.ingram_api_secret');
    }
}