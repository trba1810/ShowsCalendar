<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserShowTableSeeder extends Seeder
{
    public function run()
    {
        // Get a user id to associate shows with
        $userId = DB::table('users')->first()->id;

        // Popular shows with their TMDB IDs
        $shows = [
            [
                'tmdb_id' => 1396,  // Breaking Bad
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'tmdb_id' => 94997, // House of the Dragon
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'tmdb_id' => 1399,  // Game of Thrones
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'tmdb_id' => 66732, // Stranger Things
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'tmdb_id' => 84773, // The Rings of Power
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('user_shows')->insert($shows);
    }
}