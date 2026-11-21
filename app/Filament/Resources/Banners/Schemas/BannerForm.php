<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image')
                    ->label('Gambar banner')
                    ->image()
                    ->disk('public')
                    ->directory('banners')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/webp', 'image/jpeg', 'image/png'])
                    ->maxSize(4096)
                    ->required()
                    ->helperText('Rasio 16:9. WebP disarankan agar ringan.'),
                TextInput::make('title')
                    ->label('Judul (internal)'),
                TextInput::make('alt')
                    ->label('Teks alternatif')
                    ->helperText('Dibaca screen reader. Kosongkan untuk memakai judul.'),
                TextInput::make('link_url')
                    ->label('Link tujuan')
                    ->helperText('Opsional, boleh relatif (mis. /game/mobile-legends). Kosongkan bila banner tidak mengarah ke mana pun.'),
                TextInput::make('sort')
                    ->label('Urutan')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
