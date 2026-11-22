<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Game;
use App\Models\GameAccount;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $mobile = Category::factory()->create(['name' => 'Mobile Game', 'slug' => 'mobile-game', 'sort' => 1]);
        $pc = Category::factory()->create(['name' => 'PC Game', 'slug' => 'pc-game', 'sort' => 2]);

        $mlbb = Game::factory()->withZone()->for($mobile)->create([
            'name' => 'Mobile Legends', 'slug' => 'mobile-legends', 'publisher' => 'Moonton', 'is_popular' => true,
        ]);
        $freeFire = Game::factory()->for($mobile)->create([
            'name' => 'Free Fire', 'slug' => 'free-fire', 'publisher' => 'Garena', 'is_popular' => true,
        ]);
        $valorant = Game::factory()->for($pc)->create([
            'name' => 'Valorant', 'slug' => 'valorant', 'publisher' => 'Riot Games', 'user_id_label' => 'Riot ID', 'user_id_regex' => null,
        ]);

        foreach ([[86, 19000], [172, 38000], [257, 57000], [706, 152000]] as [$amount, $basePrice]) {
            Product::factory()->for($mlbb)->create([
                'name' => "{$amount} Diamonds", 'supplier_sku' => "ML{$amount}",
                'base_price' => $basePrice, 'price' => (int) round($basePrice * 1.05),
            ]);
        }

        foreach ([[70, 9500], [140, 19000], [355, 47000]] as [$amount, $basePrice]) {
            Product::factory()->for($freeFire)->create([
                'name' => "{$amount} Diamonds", 'supplier_sku' => "FF{$amount}",
                'base_price' => $basePrice, 'price' => (int) round($basePrice * 1.05),
            ]);
        }

        Product::factory()->for($valorant)->create([
            'name' => '475 VP', 'group' => 'Valorant Points', 'supplier_sku' => 'VAL475',
            'base_price' => 55000, 'price' => 58000,
        ]);

        // Akun game yang dijual (tabel terpisah, bukan produk)
        GameAccount::factory()->count(5)->for($mlbb)->create(['name' => 'Akun MLBB Mythic Glory', 'price' => 350000]);
        GameAccount::factory()->count(2)->for($freeFire)->create(['name' => 'Akun FF Sultan', 'price' => 200000]);
    }
}
