<?php

namespace App\Filament\Resources\GameAccounts\Schemas;

use App\Enums\AccountStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GameAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('game_id')
                    ->label('Game')
                    ->relationship('game', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label('Nama akun')
                    ->required(),
                TextInput::make('price')
                    ->label('Harga jual')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
                Select::make('status')
                    ->helperText('Akun dijual manual di luar sistem: ubah ke Terjual setelah akun diserahkan ke pembeli.')
                    ->options(AccountStatus::class)
                    ->default(AccountStatus::Available->value)
                    ->required(),
                Textarea::make('public_info')
                    ->label('Info publik')
                    ->helperText('Ditampilkan di halaman game, contoh: rank, jumlah skin, level.')
                    ->columnSpanFull(),
                FileUpload::make('images')
                    ->label('Gambar akun')
                    ->helperText('Maksimal 5 gambar (JPEG, PNG, WebP, maks 2 MB per file). Otomatis diubah ke WebP saat disimpan.')
                    ->image()
                    ->multiple()
                    ->maxFiles(5)
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/webp', 'image/jpeg', 'image/png'])
                    ->disk('public')
                    ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): ?string => self::storeAsWebp($file))
                    ->columnSpanFull(),
                DateTimePicker::make('sold_at')
                    ->label('Terjual pada')
                    ->disabled(),
            ]);
    }

    /**
     * Mengubah gambar ke WebP (kualitas 80) dan menyimpannya di disk public. Null bila file tidak bisa dibaca atau diubah GD.
     * shortcut: tanpa resize dan tanpa koreksi rotasi EXIF, dan file yang dilepas dari form tidak dihapus dari disk; upgrade bila foto kamera sering dipakai.
     */
    private static function storeAsWebp(TemporaryUploadedFile $file): ?string
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            return null;
        }

        imagepalettetotruecolor($image);

        ob_start();
        $encoded = imagewebp($image, null, 80);
        $webp = ob_get_clean();

        if (! $encoded || $webp === false || $webp === '') {
            return null;
        }

        $path = 'game-accounts/images/'.Str::random(40).'.webp';
        Storage::disk('public')->put($path, $webp);

        return $path;
    }
}
