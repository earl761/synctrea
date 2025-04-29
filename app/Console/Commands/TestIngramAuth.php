<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Supplier;


class TestIngramAuth extends Command
{
    protected $signature = 'ingram:test-auth';
    protected $description = 'Test Ingram Micro API authentication';

    public function handle()
    {
        try {
            $supplier = Supplier::where('type', 'ingram_micro')
                ->where('is_active', true)
                ->first();



            //  {"id":1,"name":"Ingram Micro Canada ","type":"ingram_micro","api_key":"eyJpdiI6InJmZDVScElMbk0wOFNxaXZhU3hnMUE9PSIsInZhbHVlIjoiRUNKRlJqOUN4QmdoQVNYUFRlRTlLVitmMGxpUU9nZXJ5NU1DcitqWDE5aDJMYTJyUWZ6SC9DQjN5MjFnS3V4cSIsIm1hYyI6ImVkN2I5OGE1OTI0OGFlMzVkODlmNmZjMjMxODQzOGFkODgyYTg4OGNlZTQ0OTg4YjRlYjFjNDJkMjVjZjM1ODIiLCJ0YWciOiIifQ==","api_secret":"eyJpdiI6ImEzcnQ2STJtVGJ4VHFOZWlJUEtmSFE9PSIsInZhbHVlIjoieG5wblRsYll6dGNYNmJhQlRDTHZ2TkJPWFdnZkp6MVQ5dUlqOEREcTJCRT0iLCJtYWMiOiI2YmRhYzk1MWJiMzc0ZDZiZWI0NTEyMGI0NmE4N2UxOGZmNGNmZGEzZThkOGZjNjJjNDM0Mjk3M2VkMmUxZTU2IiwidGFnIjoiIn0=","api_endpoint":"https:\/\/api.ingrammicro.com:443","credentials":null,"settings":null,"is_active":true,"created_at":"2025-04-15T20:43:51.000000Z","updated_at":"2025-04-15T20:43:51.000000Z","deleted_at":null}  


            if (!$supplier) {
                $this->error('No active Ingram Micro supplier found');
                return Command::FAILURE;
            }

            if (!isset($supplier->api_key) || !isset($supplier->api_secret)) {
                $this->error('Invalid supplier credentials');
                Log::error('Invalid supplier credentials');
                return Command::FAILURE;
            }

            $clientId = decrypt($supplier->api_key);
            $clientSecret = decrypt($supplier->api_secret);
            
            $this->info('Attempting authentication with:');
            $this->info('API Endpoint: ' . $supplier->api_endpoint);
            $this->info('Client ID: ' . substr($clientId, 0, 10) . '...');
            
            $response = Http::asForm()->post($supplier->api_endpoint . '/oauth/oauth30/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
                'scope' => 'basic'
            ]);

            $responseData = $response->json();
            Log::info('Ingram Micro API authentication response', $responseData);

            if ($response->successful()) {
                if (isset($responseData['access_token']) && !empty($responseData['access_token'])) {
                    $this->info('Authentication successful!');
                    $this->info('Access Token: ' . $responseData['access_token']);
                    Log::info('Ingram Micro API authentication successful', [
                        'access_token' => $responseData['access_token']
                    ]);
                    return Command::SUCCESS;
                } else {
                    $this->error('Authentication failed: Empty access token received');
                    $this->info('API Response: ' . json_encode($responseData, JSON_PRETTY_PRINT));
                    Log::error('Empty access token in response', $responseData);
                    return Command::FAILURE;
                }
            }

            $this->error('Authentication failed: ' . $response->body());
            Log::error('Ingram Micro API authentication failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Ingram Micro API authentication error', [
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }
}