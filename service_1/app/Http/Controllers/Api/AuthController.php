<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRegister;
use App\Http\Requests\RegistrationRequest;
use App\Http\Requests\ResetRequest;
use App\Http\Requests\UpdateRequest;
use App\Mail\PasswordResetMail;
use App\Mail\PasswordUpdateMail;
use App\Mail\RegistrationCompleteMail;
use App\Mail\RegistrationMail;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    protected $authService;
    public function __construct(AuthService $authService)
    {
        $this->middleware('auth:sanctum', ['except' => ['loginUser', 'createUser', 'registerVerify', 'resetPassword', 'updatePassword']]);
        $this->authService = $authService;
    }
    /*
    ----------------------------------------------------------------------------------
    | This createUser function create a user and send  a verification email.
    | First check that email already exits or not.
    | If the user exists but their email is not verified,
    | update the confirmation codeand send a new confirmation email
    | Otherwise, create a new user with the given details and send a confirmation email
    ----------------------------------------------------------------------------------
    */
    public function createUser(RegistrationRequest $request)
    {
        $exist_user = User::where('email', $request->email)->first();
        $confirm_code = Str::random(32);
        if ($exist_user && is_null($exist_user->email_verified_at)) {
            User::where('email', $request->email)->update(['confirm_code' => $confirm_code]);
            return $this->sendConfirmationEmail($exist_user, $confirm_code);
        }
        $user = User::create(
            [
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'confirm_code' => $confirm_code,

            ]
        );
        $this->sendConfirmationEmail($user, $confirm_code);
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $user->createToken("API TOKEN")->plainTextToken,
            'token_type' => 'Bearer',
        ], 200);
    }
    /*
    -----------------------------------------------------------------
    | This registerVerify function  verification email for new user.
    | First check that  valid given confirm code
    | Check valid given confirm code && check null email_verified_at.
    | If okay verified otherwise Email already verified.
    -----------------------------------------------------------------
    */
    public function registerVerify($confirm_code, Request $request)
    {
        $user = User::where('confirm_code', $confirm_code)->first();
        if (!$user) {
            return response()->json(["msg" => "Invalid user."], 400);
        }
        if (!$user->email_verified_at) {
            $user->markEmailAsVerified();
            try {
                Mail::to($user->email)->send(new RegistrationCompleteMail($user));
            } catch (\Exception $e) {
                return response()->json([
                    'msg' => 'Error sending email. Please try again later.'
                ], 500);
            }
            return response()->json([
                "msg" => "Email  verified.",
                'token' => $user->createToken("API TOKEN")->plainTextToken,
                'token_type' => 'Bearer',
            ], 200);
        }
        return response()->json(["msg" => "Email already verified."], 400);
    }
    /*
    ----------------------------------------------------------------
    | This loginUser function  userd for login.
    | Auth::once($input) will attempt to authenticate the user with
    | given credentials ($input) without actually logging them in.
    | If verified user loged in and generate token.
    ----------------------------------------------------------------
    */
    public function loginUser(LoginRegister $request)
    {
        $input = $request->only('email', 'password');

        if (Auth::once($input)) {
            $user = Auth::user();

            if (empty($user->email_verified_at)) {
                return response()->json(['error' => 'Your account is not verified.'], 403);
            }

            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully.',
                'token' => $user->createToken('API TOKEN')->plainTextToken,
                'token_type' => 'Bearer',
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid email and password.',
            'error' => 'Unauthorized'
        ], 401);
    }
    /*
    --------------------------------------------------------
    | This resetPassword function  for reset user Password.
    | First check  email exist or not with try case.
    | Then check email verified status.
    | Check token exitst in PasswordReset table.
    | Then update or insert and send a email link.
    --------------------------------------------------------
    */
    public function resetPassword(ResetRequest $request)
    {
        $confirm_code = Str::random(32);
        try {
            $exist_user = User::where('email', $request->email)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Email not exist , Create a account'], 404);
        }
        if (!$exist_user->email_verified_at) {
            return response()->json(['error' => 'Your have not verified your open account.'], 401);
        }

        $token_exitst = PasswordReset::where('email', $request->email)->first();
        if ($token_exitst) {
            PasswordReset::where('email', $request->email)->update(['token' => $confirm_code]);
        } else {
            PasswordReset::updateOrCreate(array_merge(
                [
                    'email' => $request->email,
                    'token' => $confirm_code,
                ]
            ));
        }
        $this->resetConfirmMail($exist_user, $confirm_code);
        return response()->json(['message' => 'Reset email send'], 200);
    }
    /*
    ---------------------------------------------------------
    | This updatePassword function  for update user Password.
    | First check  token details.
    | If empty Invalid Token.
    | Then update password and send email.
    ---------------------------------------------------------
    */
    public function updatePassword($confirm_code, UpdateRequest $request)
    {
        $token_details = PasswordReset::where('token', $confirm_code)->first();
        if (empty($token_details)) {
            return response()->json('Invalid Token');
        }
        User::where('email', $token_details->email)
            ->first()
            ->update(['password' => bcrypt($request->password)]);
        PasswordReset::where('email', $token_details->email)->delete();
        try {
            Mail::to($token_details->email)->send(new PasswordUpdateMail($token_details));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error sending email. Please try again later.'], 500);
        }
        return response()->json(['message' => 'Update your Password'], 200);
    }
    /*
    ---------------------------------------------------------
    | This sendConfirmationEmail email confirmation funciton.
    ---------------------------------------------------------
    */
    public function sendConfirmationEmail($exist_user, $confirm_code)
    {
        try {
            Mail::to($exist_user->email)->send(new RegistrationMail($exist_user, $confirm_code));
            return response()->json(['message' => 'Check your email']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error sending email. Please try again later.'], 500);
        }
    }
    /*
    ----------------------------------------------------------
    | This resetConfirmMail reset email confirmation funciton.
    ----------------------------------------------------------
    */
    public function resetConfirmMail($exist_user, $confirm_code)
    {
        try {
            Mail::to($exist_user->email)->send(new PasswordResetMail($exist_user, $confirm_code));
            return response()->json(['message' => 'Check your email']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error sending email. Please try again later.'], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => 'success', 'message' => 'User logged out successfully']);
    }

    public function user()
    {
        return response()->json(auth()->user());
    }
}
