<?php

namespace App\Http\Controllers;

use App\Models\WebhookPayload;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function webhook(Request $request)
    {
        // Prepare an array to hold all the records to be inserted
        $webhookEvents = [];

        $time = now();
        $eventNotifications = $request->input('eventNotifications', []);

        foreach ($eventNotifications as $eventNotification) {
            $entities = $eventNotification['dataChangeEvent']['entities'] ?? [];

            foreach ($entities as $entity) {
                // Only process if the entity is an 'Estimate'
                if ($entity['name'] === 'Estimate') {
                    $webhookEvents[] = [
                        'object_id' => $entity['id'],
                        'operation' => $entity['operation'],
                        'created_at' => $time,
                        'updated_at' => $time,
                    ];
                }
            }
        }

        $webhookEvents = array_unique($webhookEvents, SORT_REGULAR);
        // Insert all records in bulk
        if (!empty($webhookEvents)) {
            WebhookPayload::insert($webhookEvents);
        }

        // Respond with a 200 status code to acknowledge receipt
        return response()->json(['status' => 'success'], 200);
    }
}
