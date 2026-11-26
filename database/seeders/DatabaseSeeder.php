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
        $voucher = Category::factory()->create(['name' => 'Voucher', 'slug' => 'voucher', 'sort' => 3]);

        $games = [
            // Mobile Games
            [
                'category' => $mobile,
                'with_zone' => true,
                'attributes' => [
                    'name' => 'Mobile Legends',
                    'slug' => 'mobile-legends',
                    'publisher' => 'Moonton',
                    'thumbnail' => 'icon-games/mobile-legends-bang-bang.webp',
                    'is_popular' => true,
                    'sort' => 1,
                ],
                'group' => 'Diamond',
                'products' => [
                    ['86 Diamonds', 'ML86', 19000],
                    ['172 Diamonds', 'ML172', 38000],
                    ['257 Diamonds', 'ML257', 57000],
                    ['706 Diamonds', 'ML706', 152000],
                ],
                'account' => ['Akun MLBB Mythic Glory', 250000, 350000, 5],
            ],
            [
                'category' => $mobile,
                'attributes' => [
                    'name' => 'Free Fire',
                    'slug' => 'free-fire',
                    'publisher' => 'Garena',
                    'thumbnail' => 'icon-games/free-fire-x-naruto-shippuden.webp',
                    'is_popular' => true,
                    'sort' => 2,
                ],
                'group' => 'Diamond',
                'products' => [
                    ['70 Diamonds', 'FF70', 9500],
                    ['140 Diamonds', 'FF140', 19000],
                    ['355 Diamonds', 'FF355', 47000],
                    ['720 Diamonds', 'FF720', 95000],
                ],
                'account' => ['Akun FF Sultan', 150000, 200000, 2],
            ],
            [
                'category' => $mobile,
                'attributes' => [
                    'name' => 'PUBG Mobile',
                    'slug' => 'pubg-mobile',
                    'publisher' => 'Level Infinite',
                    'thumbnail' => 'icon-games/pubg-mobile.webp',
                    'is_popular' => true,
                    'sort' => 3,
                ],
                'group' => 'UC',
                'products' => [
                    ['60 UC', 'PUBG60', 14000],
                    ['325 UC', 'PUBG325', 70000],
                    ['660 UC', 'PUBG660', 140000],
                ],
                'account' => ['Akun PUBG Conqueror', 300000, 420000, 3],
            ],
            [
                'category' => $mobile,
                'with_zone' => true,
                'attributes' => [
                    'name' => 'Genshin Impact',
                    'slug' => 'genshin-impact',
                    'publisher' => 'HoYoverse',
                    'thumbnail' => 'icon-games/genshin-impact-6th-anniversary.webp',
                    'user_id_label' => 'UID',
                    'zone_id_label' => 'Server',
                    'zone_id_regex' => null,
                    'is_popular' => true,
                    'sort' => 4,
                ],
                'group' => 'Genesis Crystals',
                'products' => [
                    ['Blessing of the Welkin Moon', 'GIWELKIN', 62000],
                    ['300+30 Genesis Crystals', 'GI300', 62000],
                    ['980+110 Genesis Crystals', 'GI980', 195000],
                ],
                'account' => ['Akun Genshin AR 58', 220000, 310000, 2],
            ],
            [
                'category' => $mobile,
                'attributes' => [
                    'name' => 'Honor of Kings',
                    'slug' => 'honor-of-kings',
                    'publisher' => 'Level Infinite',
                    'thumbnail' => 'icon-games/honor-of-kings.webp',
                    'is_popular' => true,
                    'sort' => 5,
                ],
                'group' => 'Tokens',
                'products' => [
                    ['80 Tokens', 'HOK80', 15000],
                    ['240 Tokens', 'HOK240', 44000],
                    ['400 Tokens', 'HOK400', 73000],
                ],
            ],
            [
                'category' => $mobile,
                'attributes' => [
                    'name' => 'Call of Duty: Mobile',
                    'slug' => 'call-of-duty-mobile',
                    'publisher' => 'Garena',
                    'thumbnail' => 'icon-games/call-of-duty-mobile.webp',
                    'user_id_label' => 'OpenID',
                    'is_popular' => true,
                    'sort' => 6,
                ],
                'group' => 'CP',
                'products' => [
                    ['63 CP', 'CODM63', 10000],
                    ['317 CP', 'CODM317', 49000],
                    ['634 CP', 'CODM634', 98000],
                ],
            ],
            [
                'category' => $mobile,
                'attributes' => [
                    'name' => 'League of Legends: Wild Rift',
                    'slug' => 'league-of-legends-wild-rift',
                    'publisher' => 'Riot Games',
                    'thumbnail' => 'icon-games/league-of-legends-wild-rift.webp',
                    'user_id_label' => 'Riot ID',
                    'user_id_regex' => null,
                    'sort' => 7,
                ],
                'group' => 'Wild Cores',
                'products' => [
                    ['425 Wild Cores', 'WR425', 52000],
                    ['1000 Wild Cores', 'WR1000', 120000],
                ],
            ],
            [
                'category' => $mobile,
                'attributes' => [
                    'name' => 'Clash of Clans',
                    'slug' => 'clash-of-clans',
                    'publisher' => 'Supercell',
                    'thumbnail' => 'icon-games/clash-of-clans.webp',
                    'user_id_label' => 'Player Tag',
                    'user_id_regex' => null,
                    'sort' => 8,
                ],
                'group' => 'Gems',
                'products' => [
                    ['80 Gems', 'COC80', 14000],
                    ['500 Gems', 'COC500', 72000],
                    ['Gold Pass', 'COCGP', 95000],
                ],
            ],
            [
                'category' => $mobile,
                'attributes' => [
                    'name' => 'Clash Royale',
                    'slug' => 'clash-royale',
                    'publisher' => 'Supercell',
                    'thumbnail' => 'icon-games/clash-royale.webp',
                    'user_id_label' => 'Player Tag',
                    'user_id_regex' => null,
                    'sort' => 9,
                ],
                'group' => 'Gems',
                'products' => [
                    ['80 Gems', 'CR80', 14000],
                    ['500 Gems', 'CR500', 72000],
                    ['Pass Royale', 'CRPASS', 85000],
                ],
            ],
            [
                'category' => $mobile,
                'attributes' => [
                    'name' => 'NBA 2K Mobile',
                    'slug' => 'nba-2k-mobile',
                    'publisher' => '2K',
                    'thumbnail' => 'icon-games/nba-2k-mobile-basketball-game.webp',
                    'sort' => 10,
                ],
                'group' => 'Coins',
                'products' => [
                    ['500 Coins', 'NBA500', 29000],
                    ['1500 Coins', 'NBA1500', 79000],
                ],
            ],
            [
                'category' => $mobile,
                'attributes' => [
                    'name' => 'Angry Birds 2',
                    'slug' => 'angry-birds-2',
                    'publisher' => 'Rovio',
                    'thumbnail' => 'icon-games/angry-birds-2.webp',
                    'sort' => 11,
                ],
                'group' => 'Gems',
                'products' => [
                    ['120 Gems', 'AB120', 15000],
                    ['650 Gems', 'AB650', 72000],
                ],
            ],
            [
                'category' => $mobile,
                'attributes' => [
                    'name' => 'Subway Surfers',
                    'slug' => 'subway-surfers',
                    'publisher' => 'SYBO Games',
                    'thumbnail' => 'icon-games/subway-surfers.webp',
                    'sort' => 12,
                ],
                'group' => 'Keys & Coins',
                'products' => [
                    ['25 Keys', 'SS25K', 29000],
                    ['100 Keys', 'SS100K', 99000],
                ],
            ],
            // PC Games
            [
                'category' => $pc,
                'attributes' => [
                    'name' => 'GTA: San Andreas',
                    'slug' => 'gta-san-andreas',
                    'publisher' => 'Rockstar Games',
                    'thumbnail' => 'icon-games/grand-theft-auto-san-andreas.webp',
                    'user_id_label' => 'Email / Akun',
                    'user_id_regex' => null,
                    'sort' => 13,
                ],
                'group' => 'Game Key',
                'products' => [
                    ['GTA San Andreas Digital Key', 'GTASAKEY', 145000],
                ],
            ],
            [
                'category' => $pc,
                'attributes' => [
                    'name' => 'Dead Cells',
                    'slug' => 'dead-cells',
                    'publisher' => 'Motion Twin',
                    'thumbnail' => 'icon-games/dead-cells.webp',
                    'user_id_label' => 'Steam ID',
                    'user_id_regex' => null,
                    'sort' => 14,
                ],
                'group' => 'Game Key',
                'products' => [
                    ['Dead Cells Base Game', 'DCBASE', 115000],
                    ['Dead Cells Medley of Pain Bundle', 'DCBUNDLE', 195000],
                ],
            ],
            [
                'category' => $pc,
                'attributes' => [
                    'name' => 'Stardew Valley',
                    'slug' => 'stardew-valley',
                    'publisher' => 'ConcernedApe',
                    'thumbnail' => 'icon-games/stardew-valley.webp',
                    'user_id_label' => 'Steam ID',
                    'user_id_regex' => null,
                    'sort' => 15,
                ],
                'group' => 'Game Key',
                'products' => [
                    ['Stardew Valley Digital Key', 'SVKEY', 110000],
                ],
            ],
            [
                'category' => $pc,
                'attributes' => [
                    'name' => 'Terraria',
                    'slug' => 'terraria',
                    'publisher' => 'Re-Logic',
                    'thumbnail' => 'icon-games/terraria.webp',
                    'user_id_label' => 'Steam ID',
                    'user_id_regex' => null,
                    'sort' => 16,
                ],
                'group' => 'Game Key',
                'products' => [
                    ['Terraria Digital Key', 'TERKEY', 85000],
                ],
            ],
            // Voucher
            [
                'category' => $voucher,
                'attributes' => [
                    'name' => 'Steam Wallet',
                    'slug' => 'steam-wallet',
                    'publisher' => 'Valve',
                    'thumbnail' => 'icon-games/steam-mobile.webp',
                    'user_id_label' => 'Email Penerima',
                    'user_id_regex' => null,
                    'is_popular' => true,
                    'sort' => 17,
                ],
                'group' => 'Voucher IDR',
                'products' => [
                    ['Steam Wallet Rp 45.000', 'STEAM45', 46000],
                    ['Steam Wallet Rp 90.000', 'STEAM90', 91000],
                    ['Steam Wallet Rp 120.000', 'STEAM120', 121000],
                ],
            ],
        ];

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
