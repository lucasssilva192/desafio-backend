<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Movements;

class MovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 0; $i < 11; $i++) {
            $options = array("credito", "debito", "estorno");
            $rand_keys = array_rand($options, 2);
            Movements::create([
                'movement' => $options[$rand_keys[0]],
                'value' => rand(100, 1000),
                'user_id' => rand(1, 10)
            ]);
        }
    }
}
