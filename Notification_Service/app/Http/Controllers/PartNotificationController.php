<?php

namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartNotificationController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        // Validate incoming request
        $data = $request->validate([
            'recipient' => 'required|string',
            'message' => 'required|string',
        ]);

        // You can dispatch a job, send mail, store in DB, etc.
        return response()->json([
            'status' => true,
            'type' => class_basename(__CLASS__),
            'message' => 'Notification sent successfully.',
            'data' => $data
        ]);
    }  //
}
