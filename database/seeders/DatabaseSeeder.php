<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\InventoryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin User manually (Avoids Factory/Faker error)
        User::updateOrCreate(
            ['email' => 'macapagal.eivan@gmail.com'],
            [
                'name' => 'Eivan Macapagal',
                'password' => Hash::make('Admin123'),
                'role' => 'owner',
            ]
        );

        // 2. Create Test Customer
        User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Juan Dela Cruz',
                'password' => Hash::make('password123'),
                'role' => 'customer',
            ]
        );

        // 3. Create Initial Inventory Items manually
        $items = [
            [
                'part_name' => 'Engine Oil (Fully Synthetic)',
                'price' => 450.00,
                'quantity' => 50,
                'sku' => 'OIL-001',
            ],
            [
                'part_name' => 'Brake Pads (Front)',
                'price' => 250.00,
                'quantity' => 30,
                'sku' => 'BRK-001',
            ],
            [
                'part_name' => 'Drive Chain (428H)',
                'price' => 850.00,
                'quantity' => 15,
                'sku' => 'CHN-001',
            ],
        ];

        foreach ($items as $item) {
            InventoryItem::updateOrCreate(
                ['part_name' => $item['part_name']],
                $item
            );
        }
    }
}
