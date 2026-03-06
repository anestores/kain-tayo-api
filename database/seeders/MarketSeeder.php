<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketSeeder extends Seeder
{
    public function run(): void
    {
        $markets = [
            [
                'name' => 'Divisoria Market',
                'address' => 'Recto Avenue, Divisoria, Manila',
                'latitude' => 14.5995,
                'longitude' => 120.9742,
                'price_range' => 'mura',
                'opening_hours' => '5:00 AM - 7:00 PM',
                'rating' => 4.20,
            ],
            [
                'name' => 'Balintawak Market',
                'address' => 'EDSA corner A. Bonifacio Avenue, Quezon City',
                'latitude' => 14.6574,
                'longitude' => 121.0042,
                'price_range' => 'mura',
                'opening_hours' => '4:00 AM - 8:00 PM',
                'rating' => 4.30,
            ],
            [
                'name' => 'Farmers Market',
                'address' => 'Araneta Center, Cubao, Quezon City',
                'latitude' => 14.6218,
                'longitude' => 121.0530,
                'price_range' => 'katamtaman',
                'opening_hours' => '5:00 AM - 9:00 PM',
                'rating' => 4.10,
            ],
            [
                'name' => 'Cartimar Market',
                'address' => 'Taft Avenue, Pasay City',
                'latitude' => 14.5537,
                'longitude' => 120.9950,
                'price_range' => 'mura',
                'opening_hours' => '6:00 AM - 7:00 PM',
                'rating' => 3.90,
            ],
            [
                'name' => 'Nepa-Q-Mart',
                'address' => 'Nepa-Q-Mart, Quezon City',
                'latitude' => 14.6280,
                'longitude' => 121.0005,
                'price_range' => 'mura',
                'opening_hours' => '5:00 AM - 8:00 PM',
                'rating' => 4.00,
            ],
            [
                'name' => 'Pamilihang Bayan ng Las Piñas',
                'address' => 'Alabang-Zapote Road, Las Piñas City',
                'latitude' => 14.4445,
                'longitude' => 120.9930,
                'price_range' => 'mura',
                'opening_hours' => '5:00 AM - 7:00 PM',
                'rating' => 3.80,
            ],
            [
                'name' => 'Muntinlupa Public Market',
                'address' => 'National Road, Poblacion, Muntinlupa City',
                'latitude' => 14.4081,
                'longitude' => 121.0415,
                'price_range' => 'mura',
                'opening_hours' => '5:00 AM - 7:00 PM',
                'rating' => 3.70,
            ],
            [
                'name' => 'Marikina Public Market',
                'address' => 'Shoe Avenue, Sta. Elena, Marikina City',
                'latitude' => 14.6318,
                'longitude' => 121.0975,
                'price_range' => 'katamtaman',
                'opening_hours' => '5:00 AM - 8:00 PM',
                'rating' => 4.10,
            ],
            [
                'name' => 'Commonwealth Market',
                'address' => 'Commonwealth Avenue, Quezon City',
                'latitude' => 14.6810,
                'longitude' => 121.0840,
                'price_range' => 'mura',
                'opening_hours' => '5:00 AM - 7:00 PM',
                'rating' => 3.90,
            ],
            [
                'name' => 'Pasig Public Market',
                'address' => 'Market Avenue, Pasig City',
                'latitude' => 14.5764,
                'longitude' => 121.0851,
                'price_range' => 'katamtaman',
                'opening_hours' => '5:00 AM - 8:00 PM',
                'rating' => 4.00,
            ],
            [
                'name' => 'San Fernando Public Market',
                'address' => 'P. Burgos Street, San Fernando, Pampanga',
                'latitude' => 14.9667,
                'longitude' => 120.6836,
                'price_range' => 'mura',
                'opening_hours' => '4:00 AM - 6:00 PM',
                'rating' => 4.20,
            ],
            [
                'name' => 'Angeles City Public Market',
                'address' => 'Miranda Street, Angeles City, Pampanga',
                'latitude' => 15.1450,
                'longitude' => 120.5887,
                'price_range' => 'mura',
                'opening_hours' => '4:00 AM - 7:00 PM',
                'rating' => 4.00,
            ],
            [
                'name' => 'Cabanatuan Public Market',
                'address' => 'Maharlika Highway, Cabanatuan City, Nueva Ecija',
                'latitude' => 15.4868,
                'longitude' => 120.9675,
                'price_range' => 'mura',
                'opening_hours' => '4:00 AM - 6:00 PM',
                'rating' => 3.90,
            ],
            [
                'name' => 'Olongapo Public Market',
                'address' => 'Magsaysay Drive, Olongapo City, Zambales',
                'latitude' => 14.8292,
                'longitude' => 120.2830,
                'price_range' => 'mura',
                'opening_hours' => '5:00 AM - 7:00 PM',
                'rating' => 3.80,
            ],
            [
                'name' => 'Tarlac Public Market',
                'address' => 'F. Tañedo Street, Tarlac City, Tarlac',
                'latitude' => 15.4363,
                'longitude' => 120.5964,
                'price_range' => 'mura',
                'opening_hours' => '4:00 AM - 6:00 PM',
                'rating' => 3.90,
            ],
        ];

        $now = now();

        foreach ($markets as $market) {
            DB::table('markets')->updateOrInsert(
                ['name' => $market['name']],
                array_merge($market, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
