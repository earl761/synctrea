<?php

namespace App\Filament\Resources\ConnectionPairResource\Pages\Actions;

use Filament\Forms;
use Filament\Actions\Action;
use Filament\Support\Facades\FilamentIcon;

class CreateAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-plus');
        $this->label('Create');
        $this->color('primary');

        $this->modalHeading('Create Connection Pair');
        $this->modalIcon(FilamentIcon::resolve('heroicon-o-plus'));

        $this->form([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('description')
                ->maxLength(255),
        ]);

        $this->action(function (array $data): void {
            $record = \App\Filament\Resources\ConnectionPairResource::class::getModel()::create($data);

            $this->success();
        });
    }
}