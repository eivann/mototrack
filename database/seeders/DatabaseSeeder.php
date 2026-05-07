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
            ['email' => 'admin@mototrack.com'],
            [
                'name' => 'MotoTrack Admin',
                'password' => Hash::make('admin123'),
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
                'name' => 'Engine Oil (Fully Synthetic)',
                'category' => 'Maintenance',
                'price' => 450.00,
                'stock' => 50,
                'sku' => 'OIL-001',
                'description' => 'Premium engine oil for motorcycles.',
            ],
            [
                'name' => 'Brake Pads (Front)',
                'category' => 'Parts',
                'price' => 250.00,
                'stock' => 30,
                'sku' => 'BRK-001',
                'description' => 'High-performance brake pads.',
            ],
            [
                'name' => 'Drive Chain (428H)',
                'category' => 'Transmission',
                'price' => 850.00,
                'stock' => 15,
                'sku' => 'CHN-001',
                'description' => 'Heavy-duty drive chain.',
            ],
        ];

        foreach ($items as $item) {
            InventoryItem::updateOrCreate(
                ['sku' => $item['sku']],
                $item
            );
        }
    }
}
