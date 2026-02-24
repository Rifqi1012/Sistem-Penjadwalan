<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed default user accounts (no registration UI).
     */
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Admin',
                'email'    => 'admin@gmail.com',
                'password' => 'admin123',
            ],
            [
                'name'     => 'Operator',
                'email'    => 'operator@gmail.com',
                'password' => 'operator123',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name'     => $u['name'],
                    'password' => $u['password'],
                ],
            );
        }
    }
}
