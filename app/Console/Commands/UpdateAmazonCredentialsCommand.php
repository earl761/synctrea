<?php

namespace App\Console\Commands;

use App\Models\Destination;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class UpdateAmazonCredentialsCommand extends Command
{
    protected $signature = 'amazon:update-credentials
                            {--region=US : The Amazon region (US, EU, etc.)}
                            {--client-id= : The LWA client ID}
                            {--client-secret= : The LWA client secret}
                            {--refresh-token= : The LWA refresh token}
                            {--aws-key= : The AWS access key}
                            {--aws-secret= : The AWS secret key}
                            {--role-arn= : The AWS role ARN}
                            {--marketplace-id= : The Amazon marketplace ID}
                            {--seller-id= : The Amazon seller ID}';

    protected $description = 'Update Amazon SP-API credentials for an existing destination';

    public function handle()
    {
        try {
            $this->info('Updating Amazon credentials...');

            // Get the region
            $region = $this->option('region') ?? $this->ask('Enter Amazon region (US, EU, etc.)');

            // Find the existing destination
            $destination = Destination::where('region', $region)
                ->where('type', 'amazon')
                ->first();

            if (!$destination) {
                $this->error("No Amazon destination found for region: {$region}");
                return 1;
            }

            // Collect new credentials
            $clientId = $this->option('client-id') ?? $this->ask('Enter LWA client ID');
            $clientSecret = $this->option('client-secret') ?? $this->ask('Enter LWA client secret');
            $refreshToken = $this->option('refresh-token') ?? $this->ask('Enter LWA refresh token');
            $awsKey = $this->option('aws-key') ?? $this->ask('Enter AWS access key');
            $awsSecret = $this->option('aws-secret') ?? $this->ask('Enter AWS secret key');
            $roleArn = $this->option('role-arn') ?? $this->ask('Enter AWS role ARN');
            $marketplaceId = $this->option('marketplace-id') ?? $this->ask('Enter Amazon marketplace ID');
            $sellerId = $this->option('seller-id') ?? $this->ask('Enter Amazon seller ID');

            // Validate inputs
            $validator = Validator::make([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'aws_key' => $awsKey,
                'aws_secret' => $awsSecret,
                'role_arn' => $roleArn,
                'marketplace_id' => $marketplaceId,
                'seller_id' => $sellerId,
            ], [
                'client_id' => 'required|string',
                'client_secret' => 'required|string',
                'refresh_token' => 'required|string',
                'aws_key' => 'required|string',
                'aws_secret' => 'required|string',
                'role_arn' => 'required|string',
                'marketplace_id' => 'required|string',
                'seller_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }
                return 1;
            }

            // Update the credentials
            $destination->credentials = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'aws_key' => $awsKey,
                'aws_secret' => $awsSecret,
                'role_arn' => $roleArn,
                'marketplace_id' => $marketplaceId,
                'seller_id' => $sellerId,
            ];
            $destination->save();

            $this->info('✅ Amazon credentials updated successfully!');
            $this->info("Destination ID: {$destination->id}");
            $this->info("Region: {$destination->region}");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to update Amazon credentials: " . $e->getMessage());
            return 1;
        }
    }
} 