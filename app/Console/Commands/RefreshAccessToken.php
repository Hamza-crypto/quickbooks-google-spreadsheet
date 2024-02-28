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
        $oauth2LoginHelper = new OAuth2LoginHelper(env('QUICKBOOKS_API_KEY'),env('QUICKBOOKS_API_SECRET'));
        $oauth2LoginHelper->setLogForOAuthCalls(true, true, '/public/storage/abc');
        $accessTokenObj = $oauth2LoginHelper->refreshAccessTokenWithRefreshToken(env('QUICKBOOKS_REFRESH_TOKEN'));
        $accessTokenValue = $accessTokenObj->getAccessToken();
        $refreshTokenValue = $accessTokenObj->getRefreshToken();

        $this->changeEnvironmentVariable('QUICKBOOKS_ACCESS_TOKEN', $accessTokenValue);
        $this->changeEnvironmentVariable('QUICKBOOKS_REFRESH_TOKEN', $refreshTokenValue);
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