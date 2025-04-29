<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AmazonAuthController extends Controller
{
    public function redirect(Request $request, $destinationId)
    {
        $destination = Destination::findOrFail($destinationId);
        
        if ($destination->type !== 'amazon') {
            return redirect()->back()->with('error', 'Invalid destination type');
        }

        $params = [
            'application_id' => config('services.amazon.client_id'),
            'state' => $destinationId,
            'version' => 'beta',
            'redirect_uri' => route('amazon.callback'),
        ];

        return redirect('https://sellercentral.amazon.com/apps/authorize/consent?' . http_build_query($params));
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('filament.admin.resources.destinations.index')
                ->with('error', 'Amazon authorization failed: ' . $request->get('error_description'));
        }

        $destinationId = $request->get('state');
        $destination = Destination::findOrFail($destinationId);

        try {
            $response = Http::post('https://api.amazon.com/auth/o2/token', [
                'grant_type' => 'authorization_code',
                'code' => $request->get('spapi_oauth_code'),
                'client_id' => config('services.amazon.client_id'),
                'client_secret' => config('services.amazon.client_secret'),
                'redirect_uri' => route('amazon.callback'),
            ]);

            if ($response->successful()) {
                $destination->credentials = array_merge($destination->credentials ?? [], [
                    'refresh_token' => encrypt($response->json('refresh_token')),
                ]);
                $destination->save();

                return redirect()->route('filament.admin.resources.destinations.index')
                    ->with('success', 'Amazon authorization successful');
            }

            return redirect()->route('filament.admin.resources.destinations.index')
                ->with('error', 'Failed to obtain refresh token: ' . $response->body());
        } catch (\Exception $e) {
            return redirect()->route('filament.admin.resources.destinations.index')
                ->with('error', 'Error processing Amazon callback: ' . $e->getMessage());
        }
    }
}