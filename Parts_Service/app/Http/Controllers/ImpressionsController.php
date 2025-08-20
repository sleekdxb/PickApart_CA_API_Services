<?php
namespace App\Http\Controllers;

use App\Helpers\PartHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ImpressionsHelper;
class ImpressionsController extends Controller
{
    protected $headerValidationController;

    public function __construct(HeaderValidationController $headerValidationController)
    {
        $this->headerValidationController = $headerValidationController;
    }

    public function setImpressions(Request $request)
    {

        $headerValidationResponse = $this->headerValidationController->validateHeaders($request);

        // If validation fails, return the header validation error response
        if ($headerValidationResponse) {
            return $headerValidationResponse;
        }
        $validatedData = Validator::make($request->all(), [
            'doer_id' => 'required|string',
            'part_id' => 'required|string',
            'vend_id' => 'required|string',
            'acc_id' => 'required|string',
            'type' => 'required|string',
            'value' => 'required|integer',

        ]);

        // If validation fails, return response with errors
        if ($validatedData->fails()) {
            return response()->json([
                'errors' => $validatedData->errors(),
            ], 422);
        }

        // Process if validation passes
        return ImpressionsHelper::setImpressions($request);
    }
}