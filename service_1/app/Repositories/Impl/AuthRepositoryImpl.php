<?php

namespace App\Repositories\Impl;

use App\Models\PasswordReset;
use App\Models\User;
use App\Repositories\AuthRepositoryInterface;

class AuthRepositoryImpl implements AuthRepositoryInterface
{
    public function findUserByEmail (string $email): ?User
    {
        return User::where('email', $email)->first();
    }
    public function updateUserConfirmCodeByEmail(string $email, string $confirmCode): string
    {
        return User::where('email', $email)->update(['confirm_code' => $confirmCode]);
    }
    public function createUser(array $userData): User
    {
        return User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => bcrypt($userData['password']),
            'confirm_code' => $userData['confirm_code'],
        ]);
    }
    public function findByConfirmCode(string $confirm_code): ?User
    {
        $user = User::where('confirm_code', $confirm_code)->first();
        return $user;
    }
    public function tokenfindByEmail(string $email): ?PasswordReset
    {
        $token_exitst = PasswordReset::where('email', $email)->first();
        return $token_exitst;
    }
    public function updateTokenByEmail(string $email, string $confirm_code): string
    {
        return PasswordReset::where('email', $email)->update(['token' => $confirm_code]);
    }
    public function createResetToken(string $email, string $confirm_code): string
    {
        return PasswordReset::updateOrCreate(array_merge(
            [
                'email' => $email,
                'token' => $confirm_code,
            ]
        ));
    }
    public function findByToken(string $confirm_code): ?PasswordReset
    {
        return PasswordReset::where('token', $confirm_code)->first();
    }
    public function updatePassword(string $email, string $password): void
    {
        User::where('email', $email)
            ->first()
            ->update(['password' => bcrypt($password)]);
    }
    public function deleteToken(string $email): void
    {
        PasswordReset::where('email', $email)->delete();
    }
}
