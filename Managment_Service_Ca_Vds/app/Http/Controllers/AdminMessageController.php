<?php

// app/Http/Controllers/AdminMessageController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\AdminEvent;
use Illuminate\Support\Facades\Validator;
class AdminMessageController extends Controller
{
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'from' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();


        AdminEvent::dispatch(123, [
            'message' => $data['message'],
            'from' => $data['from'] ?? 'anonymous',
        ]);


        return response()->json(['status' => 'Message broadcasted']);
    }
}
