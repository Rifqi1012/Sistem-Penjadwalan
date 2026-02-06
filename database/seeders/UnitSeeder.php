<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Unit;


class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Unit::query()->delete();

        for ($i = 1; $i <= 185; $i++) {
            Unit::create([
                'code' => 'UNIT-' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'capacity_per_day' => 240,
                'is_active' => true,
            ]);
        }
    }
}
