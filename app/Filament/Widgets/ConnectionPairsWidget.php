<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\ConnectionPair;
use App\Models\User;

class ConnectionPairsWidget extends Widget
{
    protected static ?int $sort = 1;
    protected static string $view = 'filament.widgets.connection-pairs';

    public function getViewData(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $connectionPairs = [];
        if ($user && $user->isSuperAdmin()) {
            $connectionPairs = ConnectionPair::with(['supplier', 'destination'])->get();
        } elseif ($user && $user->company_id) {
            $connectionPairs = ConnectionPair::with(['supplier', 'destination'])
                ->where('company_id', $user->company_id)
                ->get();
        }
        return [
            'connectionPairs' => $connectionPairs,
        ];
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
} 