<?php

namespace App\Repositories;

use App\Models\PasswordReset;
use App\Models\User;

interface AuthRepositoryInterface
{
    public function findUserByEmail(string $email): ?User;
    public function updateUserConfirmCodeByEmail(string $email, string $confirmCode): string;
    public function createUser(array $data): User;
    public function findByConfirmCode(string $confirm_code): ?User;
    public function tokenfindByEmail(string $email): ?PasswordReset;
    public function updateTokenByEmail(string $email, string $confirm_code): string;
    public function createResetToken(string $email, string $confirm_code): string;
    public function findByToken(string $confirm_code): ?PasswordReset;
    public function updatePassword(string $email,string $password): void;
    public function deleteToken(string $email): void;
}
