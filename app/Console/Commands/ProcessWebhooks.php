<?php

namespace App\Console\Commands;

use App\Http\Controllers\QBController;
use App\Models\WebhookPayload;
use Illuminate\Console\Command;
use Exception;
use Revolution\Google\Sheets\Facades\Sheets;

class ProcessWebhooks extends Command
{
    protected $signature = 'quickbooks:process-webhooks';

    protected $description = 'Command description';

    protected $qb_controller;

    public function __construct(QBController $qb_controller)
    {
        parent::__construct();
        $this->qb_controller = $qb_controller;
    }

    public function handle()
    {

        try {
            $webhook = WebhookPayload::first();

            if(! $webhook) {
                return;
            }
            $estimateId = $webhook->object_id;

            // Fetch estimate details using QuickBooks API
            $estimate = $this->qb_controller->call("estimate/{$estimateId}");

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

            // Fetch product details including SKUs based on product IDs
            $productDetails = $this->getProductDetails($productIds);

            // Generate an array of products along with SKUs
            $productsArray = $this->generateProductsArray($lineItems, $productDetails);
            // Push data to Google Sheet
            $this->pushToGoogleSheet($webhook, $estimate, $productsArray);
        } catch(Exception $e) {
            dump($e->getMessage());
        }

        WebhookPayload::where('object_id', $estimateId)->delete();

    }

    private function getProductDetails($productIds)
    {
        // Wrap each product ID with single quotes
        $productIdsQuoted = array_map(function ($productId) {
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

                $rate_formula_75 = $rate * 0.65; //modified from .75 to .70 on 27/May/2024
                $rate_number_75 = $rate_formula_75;
                $amount_75 = $quantity * $rate_formula_75;
                $net_to_vendor = $amount_75 - $materialCost;

                $productsArray[] = [
                    "PRODUCT/SERVICE" => $productDetail['Name'] ?? '', //A
                    "SKU" => $sku, //B
                    "DESCRIPTION" => $description, //C
                    "QTY" => sprintf("%s", $quantity), //D
                    "RATE" => $rate, //E
                    "AMOUNT" => $amount, //F
                    "75% RATE" => $rate_number_75, //G
                    "75% AMOUNT" => $amount_75, //H
                    "MATERIAL COST" => $materialCost * $quantity, //I
                    "NET TO VENDOR" => $net_to_vendor //J
                ];
            }
        }

        return $productsArray;
    }

    // Push data to Google Sheet
    private function pushToGoogleSheet($requestData, $estimate, $data)
    {
        $operation = $requestData->operation; // $requestData['operation'];

        $spreadsheetId = env('SPREADSHEET_ID');

        $sheetTitle = $estimate['Estimate']['DocNumber'] . "_" . $estimate['Estimate']['Id']; // . "_" . time();


        $headerRow = [
                    "PRODUCT/SERVICE" => "PRODUCT/SERVICE",
                    "SKU" => "SKU",
                    "DESCRIPTION" => "DESCRIPTION",
                    "QTY" => "QTY",
                    "RATE" => "RATE",
                    "AMOUNT" => "AMOUNT",
                    // "75% RATE FORMULA" => "75% RATE FORMULA",
                    "75% RATE" => "70% RATE", //If we change right side of array, then it reflects in sheet header
                    "75% AMOUNT" => "70% AMOUNT",
                    "MATERIAL COST" => "MATERIAL COST",
                    "NET TO VENDOR" => "NET TO VENDOR"
                ];


        if($operation == 'Create') {
            // Create new tab with name as sheetTitle and insert data
            $sheet = Sheets::spreadsheet($spreadsheetId)->addSheet($sheetTitle);
            array_unshift($data, $headerRow);

            $sheet = Sheets::spreadsheet($spreadsheetId)->sheet($sheetTitle);
            $sheet->append($data);
        } elseif($operation == 'Update') {
            try {
                try {
                    // Delete existing tab if present
                    Sheets::spreadsheet($spreadsheetId)->deleteSheet($sheetTitle);
                    sleep(5);
                    $sheet = Sheets::spreadsheet($spreadsheetId)->addSheet($sheetTitle);
                } catch(Exception $e) {
                    $sheet = Sheets::spreadsheet($spreadsheetId)->addSheet($sheetTitle);
                }

                array_unshift($data, $headerRow);

                $sheet = Sheets::spreadsheet($spreadsheetId)->sheet($sheetTitle);
                $sheet->append($data);
            } catch(Exception $e) {
                dump($e->getMessage());
            }
        }

        echo "Data Inserted Successfully";
    }
}
