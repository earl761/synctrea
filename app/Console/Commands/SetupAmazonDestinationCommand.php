<?php

namespace App\Console\Commands;

use App\Models\Destination;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class SetupAmazonDestinationCommand extends Command
{
    protected $signature = 'amazon:setup-destination
                            {--region=US : The Amazon region (US, EU, etc.)}
                            {--name= : The name of the destination}
                            {--marketplace-id= : The Amazon marketplace ID}
                            {--seller-id= : The Amazon seller ID}
                            {--client-id= : The LWA client ID}
                            {--client-secret= : The LWA client secret}
                            {--refresh-token= : The LWA refresh token}
                            {--aws-key= : The AWS access key}
                            {--aws-secret= : The AWS secret key}
                            {--role-arn= : The AWS role ARN}';

    protected $description = 'Set up an Amazon destination with required credentials';

    public function handle()
    {
        try {
            $this->info('Setting up Amazon destination...');

            // Collect required information
            $region = $this->option('region') ?? $this->ask('Enter Amazon region (US, EU, etc.)');
            $name = $this->option('name') ?? $this->ask('Enter destination name');
            $marketplaceId = $this->option('marketplace-id') ?? $this->ask('Enter Amazon marketplace ID');
            $sellerId = $this->option('seller-id') ?? $this->ask('Enter Amazon seller ID');
            $clientId = $this->option('client-id') ?? $this->ask('Enter LWA client ID');
            $clientSecret = $this->option('client-secret') ?? $this->ask('Enter LWA client secret');
            $refreshToken = $this->option('refresh-token') ?? $this->ask('Enter LWA refresh token');
            $awsKey = $this->option('aws-key') ?? $this->ask('Enter AWS access key');
            $awsSecret = $this->option('aws-secret') ?? $this->ask('Enter AWS secret key');
            $roleArn = $this->option('role-arn') ?? $this->ask('Enter AWS role ARN');

            // Validate inputs
            $validator = Validator::make([
                'region' => $region,
                'name' => $name,
                'marketplace_id' => $marketplaceId,
                'seller_id' => $sellerId,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'aws_key' => $awsKey,
                'aws_secret' => $awsSecret,
                'role_arn' => $roleArn,
            ], [
                'region' => 'required|string',
                'name' => 'required|string',
                'marketplace_id' => 'required|string',
                'seller_id' => 'required|string',
                'client_id' => 'required|string',
                'client_secret' => 'required|string',
                'refresh_token' => 'required|string',
                'aws_key' => 'required|string',
                'aws_secret' => 'required|string',
                'role_arn' => 'required|string',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }
                return 1;
            }

            // Create or update the destination
            $destination = Destination::updateOrCreate(
                [
                    'region' => $region,
                    'type' => Destination::TYPE_AMAZON,
                ],
                [
                    'name' => $name,
                    'marketplace_id' => $marketplaceId,
                    'seller_id' => $sellerId,
                    'credentials' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'refresh_token' => $refreshToken,
                        'aws_key' => $awsKey,
                        'aws_secret' => $awsSecret,
                        'role_arn' => $roleArn,
                    ],
                    'is_active' => true,
                ]
            );

            $this->info('✅ Amazon destination set up successfully!');
            $this->info("Destination ID: {$destination->id}");
            $this->info("Region: {$destination->region}");
            $this->info("Marketplace ID: {$destination->marketplace_id}");
            $this->info("Seller ID: {$destination->seller_id}");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to set up Amazon destination: " . $e->getMessage());
            return 1;
        }
    }
} 