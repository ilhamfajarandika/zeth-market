<?php

namespace App\Filament\Resources\Games\Schemas;

use App\Services\Supplier\MeloCatalogService;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Throwable;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('melo_brand_id')
                    ->label('Brand Melo (opsional)')
                    ->helperText('Tautkan ke brand Melo agar item/voucher bisa di-sync. Game tanpa tautan tetap bisa dibuat manual.')
                    ->options(fn (): array => app(MeloCatalogService::class)->brandOptions())
                    ->searchable()
                    ->live()
                    ->unique(ignoreRecord: true)
                    ->afterStateUpdated(fn (?string $state, Get $get, Set $set) => self::fillFromMeloBrand($state, $get, $set))
                    ->columnSpanFull(),
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set, string $operation): void {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                TextInput::make('publisher'),
                TextInput::make('thumbnail'),
                TextInput::make('banner'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('instruction')
                    ->columnSpanFull(),
                Toggle::make('requires_zone_id')
                    ->label('Perlu Zone/Server ID')
                    ->live(),
                TextInput::make('user_id_label')
                    ->required()
                    ->default('User ID'),
                TextInput::make('zone_id_label')
                    ->required(fn (Get $get): bool => (bool) $get('requires_zone_id')),
                TextInput::make('user_id_regex')
                    ->helperText('Tanpa delimiter, contoh: ^\d{6,12}$')
                    ->rule(self::validRegex()),
                TextInput::make('zone_id_regex')
                    ->helperText('Tanpa delimiter, contoh: ^\d{3,5}$')
                    ->required(fn (Get $get): bool => (bool) $get('requires_zone_id'))
                    ->rule(self::validRegex()),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Toggle::make('is_popular')
                    ->label('Populer'),
                TextInput::make('sort')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    /**
     * Isi nama, slug, thumbnail, dan label input dari brand Melo yang dipilih (tidak menimpa isian admin).
     */
    private static function fillFromMeloBrand(?string $brandId, Get $get, Set $set): void
    {
        if (blank($brandId)) {
            return;
        }

        $catalog = app(MeloCatalogService::class);

        try {
            $brand = $catalog->brand((int) $brandId);
            $defaults = $catalog->inquiryDefaults((int) $brandId);
        } catch (Throwable) {
            return;
        }

        if ($brand !== null) {
            blank($get('name')) && $set('name', $brand['name']);
            blank($get('slug')) && $set('slug', Str::slug($brand['slug'] ?: $brand['name']));
        }

        blank($get('thumbnail')) && filled($defaults['thumbnail']) && $set('thumbnail', $defaults['thumbnail']);
        $set('requires_zone_id', $defaults['requires_zone_id']);
        filled($defaults['user_id_label']) && $set('user_id_label', $defaults['user_id_label']);
        filled($defaults['zone_id_label']) && $set('zone_id_label', $defaults['zone_id_label']);
    }

    /**
     * Pola regex tanpa delimiter harus bisa dikompilasi PHP.
     */
    private static function validRegex(): Closure
    {
        return fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
            if (blank($value)) {
                return;
            }

            if (@preg_match('/'.str_replace('/', '\/', (string) $value).'/', '') === false) {
                $fail('Pola regex tidak valid.');
            }
        };
    }
}
