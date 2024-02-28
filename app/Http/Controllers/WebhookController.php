<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use Exception;
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
        // Extract the estimate ID from the webhook request
        $estimateId = 13709; //$request->input('estimate_id');

        // Fetch estimate details using QuickBooks API
        $estimate = $this->qb_controller->call("estimate/{$estimateId}");

        $privateNote = $estimate['Estimate']['Id'] . "_" . $estimate['Estimate']['PrivateNote'];

        // Extract necessary data from the estimate response
        $lineItems = $estimate['Estimate']['Line'];

        // Initialize an array to store product IDs
        $productIds = [];

        // Iterate over line items to fetch product IDs
        foreach ($lineItems as $lineItem) {
            $productId = $lineItem['SalesItemLineDetail']['ItemRef']['value'] ?? null;
            if ($productId) {
                $productIds[] = $productId;
            }
        }
dump($productIds);
        // Fetch product details including SKUs based on product IDs
        $productDetails = $this->getProductDetails($productIds);
dump($productDetails);
        // Generate an array of products along with SKUs
        $productsArray = $this->generateProductsArray($lineItems, $productDetails);
dump($productsArray);
        // Push data to Google Sheet
        $this->pushToGoogleSheet($request, $productsArray, $privateNote);
    }

    // Fetch product details including SKUs based on product IDs
    private function getProductDetails($productIds)
{
    // Wrap each product ID with single quotes
    $productIdsQuoted = array_map(function($productId) {
        return "'$productId'";
    }, $productIds);

    // Construct the query with product IDs inside quotes
    $query = "select * from Item where id in (" . implode(',', $productIdsQuoted) . ")";

    // Fetch product details using QuickBooks API
    $productDetails = $this->qb_controller->query($query);

    return $productDetails['QueryResponse']['Item'];
}

    // Generate an array of products along with SKUs
    private function generateProductsArray($lineItems, $productDetails)
    {
        $productsArray = [];

        foreach ($lineItems as $lineItem) {
            $productId = $lineItem['SalesItemLineDetail']['ItemRef']['value'] ?? null;
            $description = $lineItem['Description'] ?? '';
            $quantity = $lineItem['SalesItemLineDetail']['Qty'] ?? '';
            $rate = $lineItem['SalesItemLineDetail']['UnitPrice'] ?? '';
            $amount = $lineItem['Amount'] ?? '';

            // Find product details by ID
            $productDetail = collect($productDetails)->firstWhere('Id', $productId);

            if ($productDetail) {
                $sku = $productDetail['Sku'] ?? '';
                $materialCost = $productDetail['PurchaseCost'] ?? '';

                // Add each line item to the array with all necessary columns
                $productsArray[] = [
                    "PRODUCT/SERVICE" => $productDetail['Name'] ?? '',
                    "DESCRIPTION" => $description,
                    "SKU" => $sku,
                    "QTY" => $quantity,
                    "RATE" => $rate,
                    "AMOUNT" => $amount,
                    "MATERIAL COST" => $materialCost
                ];
        }
        }

        return $productsArray;
    }

    // Push data to Google Sheet
    private function pushToGoogleSheet($data, $sheetTitle)
    {
        $spreadsheetId = env('SPREADSHEET_ID');


        try{
             // Delete existing tab if present
        Sheets::spreadsheet($spreadsheetId)->deleteSheet($sheetTitle);
        }
        catch(Exception $e){
            dump($e->getMessage());
        }


        // Create new tab with name as PrivateNote and insert data
        $sheet = Sheets::spreadsheet($spreadsheetId)->addSheet($sheetTitle);

        $sheet = Sheets::spreadsheet($spreadsheetId)->sheet($sheetTitle);
        $sheet->append($data);

        // $sheet = Sheets::spreadsheet($spreadsheetId)->sheet($sheetTitle);
        // $sheet->append($data);
    }
}
