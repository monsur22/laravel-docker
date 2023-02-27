<?php

namespace App\Services\Impl;

use App\Mail\RegistrationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Services\AuthServiceInterface;
use App\Mail\PasswordResetMail;
use App\Mail\PasswordUpdateMail;
use App\Mail\RegistrationCompleteMail;
use App\Repositories\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class AuthServiceImpl implements AuthServiceInterface
{
    protected $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    /*
    ----------------------------------------------
    | Please focus on controller comment detials.
    ----------------------------------------------
    */
    public function createUser($request)
    {
        $exist_user = $this->authRepository->findUserByEmail($request->email);
        $confirm_code = Str::random(32);
        if ($exist_user && $this->isAccountNotVerified($exist_user)) {
            $this->authRepository->updateUserConfirmCodeByEmail($request->email, $confirm_code);
            return $this->sendConfirmationEmail($exist_user, $confirm_code);
        }
        $userData = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'confirm_code' => $confirm_code,
        ];
        $user = $this->authRepository->createUser($userData);
        $this->sendConfirmationEmail($user, $confirm_code);
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $user->createToken("API TOKEN")->plainTextToken,
            'token_type' => 'Bearer',
        ], 200);
    }
    /*
    ----------------------------------------------
    | Please focus on controller comment detials.
    ----------------------------------------------
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
    ----------------------------------------------
    | Please focus on controller comment detials.
    ----------------------------------------------
    */
    public function verifyRegistration($confirm_code)
    {
        $user = $this->authRepository->findByConfirmCode($confirm_code);
        if (!$user) {
            return response()->json(["msg" => "Invalid user."], 400);
        }
        if ($this->isAccountNotVerified($user)) {
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
    ----------------------------------------------
    | Please focus on controller comment detials.
    ----------------------------------------------
    */
    public function loginUser($request)
    {
        $input = $request->only('email', 'password');
        if (Auth::once($input)) {
            $user = Auth::user();
            if ($this->isAccountNotVerified($user)) {
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
    ----------------------------------------------
    | Please focus on controller comment detials.
    ----------------------------------------------
    */
    public function resetPassword($request)
    {
        $exist_user = $this->authRepository->findUserByEmail($request->email);
        if (!$exist_user) {
            return response()->json(['error' => 'Email not exist , Create a account'], 404);
        }
        if ($this->isAccountNotVerified($exist_user)) {
            return response()->json(['error' => 'Your have not verified your open account.'], 401);
        }
        $confirm_code = Str::random(32);
        $token_exitst = $this->authRepository->tokenfindByEmail($request->email);
        if ($token_exitst) {
            $this->authRepository->updateTokenByEmail($request->email, $confirm_code);
        } else {
            $this->authRepository->createResetToken($request->email, $confirm_code);
        }
        $this->resetConfirmMail($exist_user, $confirm_code);
        return response()->json(['message' => 'Reset email send'], 200);
    }
    /*
    ---------------------------------------------------------
    | This sendConfirmationEmail email confirmation funciton.
    ---------------------------------------------------------
    */
    public function updatePassword($confirm_code, $request)
    {
        $token_details = $this->authRepository->findByToken($confirm_code);
        if (empty($token_details)) {
            return response()->json(['message' => 'Invalid Token'], 200);
        }
        $this->authRepository->updatePassword($token_details->email, $request->password);
        $this->authRepository->deleteToken($token_details->email);
        try {
            Mail::to($token_details->email)->send(new PasswordUpdateMail($token_details));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error sending email. Please try again later.'], 500);
        }
        return response()->json(['message' => 'Successfully Update your Password'], 200);
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
    /*
    ----------------------------------------------------------
    | This isAccountNotVerified function check verified or not.
    ----------------------------------------------------------
    */
    public function isAccountNotVerified(User $user): bool
    {
        return empty($user->email_verified_at);
    }
}
