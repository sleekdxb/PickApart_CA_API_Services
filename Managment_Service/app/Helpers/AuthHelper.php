<?php
namespace App\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Exception;

class AuthHelper
{

    public static function resetStaffPassword(Request $request)
    {
        $staff = Staff::where('staff_id', $request->staff_id)->first();

        if (!$staff) {
            return response()->json([
                'status' => false,
                'message' => 'Staff not found.',
            ], 404);
        }

        $staff->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Password reset successfully.',
        ]);
    }
    public static function staffLogin(Request $request)
    {
        try {


            $email = $request->input('work_email');
            $password = $request->input('password');


            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email format.',
                ], 400);
            }

            $staff = Staff::where('work_email', $email)->first();

            if (!$staff || !Hash::check($password, $staff->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email or password.',
                ], 401);
            }

            // Set custom claims and expiration (24 hours = 1440 minutes)
            $expiresAt = now()->addMinutes(1440);
            $customClaims = [
                'exp' => $expiresAt->timestamp,
            ];

            $token = JWTAuth::fromUser($staff, $customClaims);

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    'token' => $token,
                    'expires_at' => $expiresAt->toDateTimeString(),
                    'staff' => $staff,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred during login.',
                'error' => $e->getMessage(), // Optional: Remove in production
            ], 500);
        }
    }



    public static function addStaff(Request $request)
    {
        try {
            $staff = new Staff();

            $staff->staff_id = Str::uuid(); // Auto-generated hashed ID
            $staff->first_name = $request->input('first_name');
            $staff->last_name = $request->input('last_name');
            $staff->work_email = $request->input('work_email');
            $staff->password = Hash::make($request->input('password'));
            $staff->job_title = $request->input('job_title');
            $staff->phone = $request->input('phone');
            $staff->passport_number = $request->input('passport_number');
            $staff->passport_expire_date = $request->input('passport_expire_date');
            $staff->salary = $request->input('salary');
            $staff->working_shift = $request->input('working_shift');
            $staff->department = $request->input('department');
            $staff->job_description = $request->input('job_description');
            $staff->shift_start_time = $request->input('shift_start_time');
            $staff->shift_end_time = $request->input('shift_end_time');

            $staff->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Staff created successfully',
                'staff_id' => $staff->staff_id
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create staff',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
