<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Mail\PasswordUpdateMail;
use App\Mail\RegistrationCompleteMail;
use App\Mail\RegistrationMail;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum', ['except' => ['loginUser', 'createUser', 'registerVerify', 'resetPassword', 'updatePassword']]);
    }
    public function createUser(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100', Rule::unique((new User)->getTable(), 'email')->whereNotNull('email_verified_at'),
            'password' => 'required|string|confirmed|min:5',
        ]);


        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $confirm_code = Str::random(32);
        $exist_user = User::where('email', $request->email)->first();
        if ($exist_user && $exist_user->email_verified_at == null) {
            User::where('email', $request->email)->update(['confirm_code' => $confirm_code]);
            Mail::to($exist_user->email)->send(new RegistrationMail($exist_user, $confirm_code));
            if (Mail::flushMacros()) {
                return response()->json('Sorry! Please try again latter');
            } else {
                return response()->json('Great! Successfully send in your mail');
            }
        }
        $user = User::create(array_merge(
            $validator->validated(),
            [
                'password' => bcrypt($request->password),
                'confirm_code' => $confirm_code,
            ]
        ));
        Mail::to($user->email)->send(new RegistrationMail($user, $confirm_code));
        if (Mail::flushMacros()) {
            return response()->json('Sorry! Please try again latter');
        } else {
            return response()->json('Great! Successfully send in your mail');
        }
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $user->createToken("API TOKEN")->plainTextToken,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function registerVerify($confirm_code, Request $request)
    {
        $user = User::where('confirm_code', $confirm_code)->first();
        if ($user && empty($user->email_verified_at)) {
            $user->markEmailAsVerified();
            Mail::to($user->email)->send(new RegistrationCompleteMail($user));
            return response()->json([
                "msg" => "Email  verified.",
                'token' => $user->createToken("API TOKEN")->plainTextToken,
                'token_type' => 'Bearer',
            ], 200);
        }

        if ($user && !empty($user->email_verified_at)) {
            return response()->json(["msg" => "Already verified"], 200);
        }

        if (!$request->hasValidSignature()) {
            return response()->json(["msg" => "Invalid user."], 400);
        }

        return response()->json(["msg" => "Email already verified."], 400);
    }

    public function loginUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:5',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!Auth::attempt($validator->validated())) {
            return response()->json(['status' => 'failed', 'message' => 'Invalid email and password.', 'error' => 'Unauthorized'], 401);
        }
        if (empty(auth()->user()->email_verified_at)) {
            return response()->json(['error' => 'Your have not verified your email.'], 401);
        }
        $user = User::where('email', $request->email)->first();

        return response()->json([
            'status' => true,
            'message' => 'User Logged In Successfully',
            'token' => $user->createToken("API TOKEN")->plainTextToken,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        $confirm_code = Str::random(32);
        $exist_user = User::where('email', $request->email)->first();
        if ($exist_user && $exist_user->email_verified_at != null) {
            $token_exitst = PasswordReset::where('email', $request->email)->first();
            if($token_exitst){
                PasswordReset::where('email', $request->email)->update(array_merge(
                $validator->validated(),
                ['token' => $confirm_code]
            ));
            }else {
                PasswordReset::create(array_merge(
                    $validator->validated(),
                    [
                        'email' => $request->email,
                        'token' => $confirm_code,
                    ]
                ));
            }

            Mail::to($exist_user->email)->send(new PasswordResetMail($exist_user, $confirm_code));
            if (Mail::flushMacros()) {
                return response()->json('Sorry! Please try again latter');
            } else {
                return response()->json('Great! Successfully send in your mail');
            }
        } elseif ($exist_user && $exist_user->email_verified_at == null) {
            return response()->json(['error' => 'Your have not verified your open account.'], 401);
        }
        return response()->json(['error' => 'Email not exist , Create a account'], 401);
    }

    public function updatePassword($confirm_code, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|confirmed|min:5',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $token_details = PasswordReset::where( 'token',$confirm_code)->first();
        if (empty($token_details)) {
            return response()->json('Invalid Token');
        }
        User::where('email', $token_details->email)
            ->first()
            ->update(['password' => bcrypt($request->password)]);
        PasswordReset::where('email',$token_details->email)->delete();
        Mail::to($token_details->email)->send(new PasswordUpdateMail($token_details));
        if (Mail::flushMacros()) {
            return response()->json('Sorry! Please try again latter');
        } else {
            return response()->json('Password update Successfully');
        }
        return response()->json(['message' => 'Update your Password'], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => 'success', 'message' => 'User logged out successfully']);
    }
    /**
     * Get the Auth user using token.
     * @return \Illuminate\Http\JsonResponse
     */
    public function user()
    {
        return response()->json(auth()->user());
    }
}
