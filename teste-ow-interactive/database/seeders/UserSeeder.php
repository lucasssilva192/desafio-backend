<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i = 0; $i < 11; $i++){
            User::create([
                'name' => 'Usuario ' . $i,
                'email' => 'usuario' . $i . '@gmail.com', 
                'birthday' => '2000-01-01',
                'initial_balance' => rand(100,1000)
            ]);
        }
    }
}
