<?php

namespace App\Services;
use App\Models\User;

interface AuthServiceInterface
{
    public function createUser($request);
    public function sendConfirmationEmail($exist_user, $confirm_code);
    public function verifyRegistration($confirm_code);
    public function loginUser($request);
    public function resetPassword($request);
    public function updatePassword($confirm_code, $request);
    public function resetConfirmMail(string $exist_user, string $confirm_code);
    public function isAccountNotVerified(User $user): bool;
}
