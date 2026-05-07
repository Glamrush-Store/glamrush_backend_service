<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'phone' => '+1234567890'],
            ['name' => 'Bob Smith', 'email' => 'bob@example.com', 'phone' => '+1234567891'],
            ['name' => 'Carol White', 'email' => 'carol@example.com', 'phone' => '+1234567892'],
            ['name' => 'David Brown', 'email' => 'david@example.com', 'phone' => '+1234567893'],
            ['name' => 'Eva Martinez', 'email' => 'eva@example.com', 'phone' => '+1234567894'],
        ];

        foreach ($customers as $data) {
            User::create([
                ...$data,
                'password' => Hash::make('password'),
            ]);
        }
    }
}
