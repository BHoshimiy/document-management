<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            ['password' => 'password', 'role' => UserRole::Admin, 'status' => UserStatus::Active]
        );

        User::updateOrCreate(
            ['username' => 'moderator'],
            ['password' => 'password', 'role' => UserRole::Moderator, 'status' => UserStatus::Active]
        );

        $client = User::updateOrCreate(
            ['username' => 'client'],
            ['password' => 'password', 'role' => UserRole::Client, 'status' => UserStatus::Active]
        );

        Company::updateOrCreate(
            ['user_id' => $client->id],
            ['name' => 'icofex Demo Farm', 'inn' => '300123456', 'address' => 'Tashkent, Uzbekistan']
        );
    }
}
