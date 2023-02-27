<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRegister;
use App\Http\Requests\RegistrationRequest;
use App\Http\Requests\ResetRequest;
use App\Http\Requests\UpdateRequest;
use App\Services\AuthServiceInterface;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;
    public function __construct(AuthServiceInterface $authService)
    {
        $this->middleware('auth:sanctum', ['except' => ['loginUser', 'createUser', 'verifyRegistration', 'resetPassword', 'updatePassword']]);
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
        $user = $this->authService->createUser($request);
        return $user;
    }
    /*
    -----------------------------------------------------------------
    | This verifyRegistration function  verification email for new user.
    | First check that  valid given confirm code
    | Check valid given confirm code && check null email_verified_at.
    | If okay verified otherwise Email already verified.
    -----------------------------------------------------------------
    */
    public function verifyRegistration($confirm_code)
    {
        $user = $this->authService->verifyRegistration($confirm_code);
        return $user;
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
        $user_details = $this->authService->loginUser( $request);
        return $user_details;
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
        $exist_user = $this->authService->resetPassword( $request);
        return $exist_user;
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
        $update_password = $this->authService->updatePassword( $confirm_code,  $request);
        return $update_password;
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
