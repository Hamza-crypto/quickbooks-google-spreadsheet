<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleSheetsService;
use Illuminate\Support\Facades\Http;

class QBController extends Controller
{
    public function call($endpoint)
    {
        $url = sprintf("%s/v3/company/%s/%s?minorversion=%s", env('QUICKBOOKS_BASE_URL'), env('QUICKBOOKS_COMPANY_ID'), $endpoint, env('QUICKBOOKS_MINOR_VERSION') );

        $response = Http::withToken(env('QUICKBOOKS_ACCESS_TOKEN'))->withHeaders([
            'Accept' => 'application/json'
        ])->get($url);

        return $response->json();

    }

    public function query($query)
    {
        $url = sprintf("%s/v3/company/%s/query?query=%s&minorversion=%s", env('QUICKBOOKS_BASE_URL'), env('QUICKBOOKS_COMPANY_ID'), $query, env('QUICKBOOKS_MINOR_VERSION') );

        $response = Http::withToken(env('QUICKBOOKS_ACCESS_TOKEN'))->withHeaders([
            'Accept' => 'application/json'
        ])->get($url);

        return $response->json();

    }
}