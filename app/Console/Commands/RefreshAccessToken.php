<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;

class RefreshAccessToken extends Command
{

    protected $signature = 'quickbooks:refresh-access-token';

    protected $description = 'Command description';

     public function handle()
     {
        $clientId = env('QUICKBOOKS_API_KEY');
        $clientSecret = env('QUICKBOOKS_API_SECRET');

        $response = Http::asForm()->withBasicAuth($clientId, $clientSecret)
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',

        ])
        ->post(env('QB_TOKEN_URL'), [
            'refresh_token' => env('QUICKBOOKS_REFRESH_TOKEN'),
            'grant_type' => 'refresh_token',
        ]);

        $responseData = $response->json();

        $this->changeEnvironmentVariable('QUICKBOOKS_ACCESS_TOKEN', $responseData['access_token']);
        $this->changeEnvironmentVariable('QUICKBOOKS_REFRESH_TOKEN', $responseData['refresh_token']);

        dump('Token Updated');
    }

    private function changeEnvironmentVariable($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {

            file_put_contents($path, str_replace(
                $key.'='.env($key), $key.'='.$value, file_get_contents($path)
            ));
        }
    }
}