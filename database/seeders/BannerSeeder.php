<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'image' => 'banners/banner1.webp',
                'title' => 'Banner 1',
                'alt' => 'Banner promo top up game',
                'sort' => 1,
            ],
            [
                'image' => 'banners/banner2.webp',
                'title' => 'Banner 2',
                'alt' => 'Banner promo top up game',
                'sort' => 2,
            ],
        ];

        foreach ($banners as $banner) {
            if (! Storage::disk('public')->exists($banner['image'])) {
                continue;
            }

            Banner::query()->updateOrCreate(
                ['image' => $banner['image']],
                [...$banner, 'link_url' => null, 'is_active' => true],
            );
        }
    }
}
