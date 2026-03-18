<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['code' => 'MANUAL_BANK_TRANSFER', 'name' => 'Manual Bank Transfer', 'is_manual' => true],
            ['code' => 'CASH', 'name' => 'Cash', 'is_manual' => true],
            ['code' => 'JAZZCASH', 'name' => 'JazzCash', 'is_manual' => false],
            ['code' => 'EASYPAISA', 'name' => 'EasyPaisa', 'is_manual' => false],
            ['code' => 'CARD', 'name' => 'Card', 'is_manual' => false],
            ['code' => 'OTHER', 'name' => 'Other', 'is_manual' => true],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                [
                    'name' => $method['name'],
                    'is_manual' => $method['is_manual'],
                    'is_active' => true,
                ]
            );
        }
    }
}
