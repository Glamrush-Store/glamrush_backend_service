<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\CustomerAddress;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Database\Seeder;

class CustomerAddressSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::limit(2)->get();

        if ($users->isEmpty()) {
            return;
        }

        $samples = [
            [
                'label' => 'Home',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'phone' => '+2348000000001',
                'address_line_1' => '12 Bode Thomas Street',
                'address_line_2' => 'Flat 3B',
                'country' => 'NG',
                'state' => 'Lagos',
                'city' => 'Surulere',
                'postal_code' => '101212',
                'is_default' => true,
            ],
            [
                'label' => 'Office',
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'phone' => '+2348000000002',
                'address_line_1' => '7 Adeola Odeku Street',
                'address_line_2' => null,
                'country' => 'NG',
                'state' => 'Lagos',
                'city' => 'Victoria Island',
                'postal_code' => '101233',
                'is_default' => false,
            ],
        ];

        foreach ($users as $user) {
            foreach ($samples as $sample) {
                CustomerAddress::firstOrCreate(
                    ['user_id' => $user->id, 'address_line_1' => $sample['address_line_1']],
                    array_merge($sample, ['user_id' => $user->id]),
                );
            }
        }
    }
}
