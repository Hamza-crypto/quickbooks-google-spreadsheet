<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use Google\Service\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\Invoice;
use Illuminate\Support\Facades\File;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use Revolution\Google\Sheets\Facades\Sheets;

class WebhookController extends Controller
{
    public $qb_controller;

    public function __construct()
    {
        $this->qb_controller = new QBController();
    }

    public function webhook(Request $request)
    {


    //     $jsonData = $request->getContent();
    //     $data = json_decode($jsonData, true);


    //    $estimate = $data['eventNotifications'][0]['dataChangeEvent']['entities'][0];
    //    if($estimate['name'] != 'Estimate') return;

    //    $estimate_id = $estimate['id'];

        // $estimate_respone = $qb_controller->call('estimate/965');

        $jsonString = File::get(public_path('estimates.json'));
        $data = json_decode($jsonString, true);

         // Extracting necessary data from the JSON response
        $estimate = $data['Estimate'];
        $customerEmail = $estimate['BillEmail']['Address'] ?? '';
        $estimateId = $estimate['Id'];
        $sheetTitle = $estimate['BillAddr']['Line1'] . "_" . $estimate['Id'];
        $sheetTitle = "Main";

         // Constructing the array header row
        $headerRow = [ 'Product','Description', 'SKU', 'Quantity', 'Rate', 'Amount'];

        // Constructing the array for data rows
    $rowData = [];
    foreach ($estimate['Line'] as $lineItem) {
        $product = $lineItem['SalesItemLineDetail']['ItemRef']['name'] ?? '';
        $description = $lineItem['Description'] ?? '';
        $sku = $lineItem['SalesItemLineDetail']['ItemRef']['value'] ?? '';
        $quantity = $lineItem['SalesItemLineDetail']['Qty'] ?? '';
        $rate = $lineItem['SalesItemLineDetail']['UnitPrice'] ?? '';
        $amount = $lineItem['Amount'] ?? '';

        // Add a new row for each line item
        $rowData[] = [$product,$description, $sku, $quantity, $rate, $amount];
    }

        // Add the header row as the first row of data
        array_unshift($rowData, $headerRow);
dd($rowData);
        $spreadsheetId = env('SPREADSHEET_ID');
        $sheet = Sheets::spreadsheet($spreadsheetId)->sheet($sheetTitle);
        $sheet->append($rowData);
    }



}