<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Services\Payment\DuitkuService;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan')
                    ->icon(Heroicon::OutlinedReceiptPercent)
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        TextEntry::make('invoice')
                            ->label('Invoice')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->weight(FontWeight::Bold),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->size(TextSize::Large),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime(),
                    ]),
                Section::make('Rincian harga')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        TextEntry::make('price')
                            ->label('Harga produk')
                            ->money('IDR', locale: 'id', decimalPlaces: 0),
                        TextEntry::make('fee')
                            ->label('Biaya admin')
                            ->money('IDR', locale: 'id', decimalPlaces: 0),
                        TextEntry::make('total')
                            ->label('Total')
                            ->money('IDR', locale: 'id', decimalPlaces: 0)
                            ->weight(FontWeight::Bold)
                            ->size(TextSize::Large)
                            ->color('primary'),
                    ]),
                Section::make('Produk & tujuan')
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('game.name')
                            ->label('Game'),
                        TextEntry::make('product_name')
                            ->label('Nama produk saat pesan'),
                        TextEntry::make('product.name')
                            ->label('Produk saat ini')
                            ->placeholder('-'),
                        TextEntry::make('target_user_id')
                            ->label('ID tujuan')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->placeholder('-'),
                        TextEntry::make('target_zone_id')
                            ->label('Zone ID')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->placeholder('-'),
                    ]),
                Section::make('Pembeli')
                    ->icon(Heroicon::OutlinedUser)
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Pengguna')
                            ->placeholder('-'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable()
                            ->placeholder('-'),
                        TextEntry::make('whatsapp')
                            ->label('WhatsApp')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->placeholder('-'),
                    ]),
                Section::make('Pembayaran')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('payment_method')
                            ->label('Metode bayar')
                            ->placeholder('-'),
                        TextEntry::make('payment_type')
                            ->label('Jenis')
                            ->state(fn (Order $record): ?string => $record->payment_method ? DuitkuService::channelGroup($record->payment_method) : null)
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('payment_reference')
                            ->label('Referensi pembayaran')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->placeholder('-'),
                        TextEntry::make('paid_at')
                            ->label('Dibayar pada')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('expired_at')
                            ->label('Kedaluwarsa pada')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('payment_code')
                            ->label('Kode pembayaran')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->placeholder('-')
                            ->visible(fn (Order $record): bool => ! self::isQris($record))
                            ->columnSpanFull(),
                        ImageEntry::make('payment_qr')
                            ->label('QR code')
                            ->state(fn (Order $record): ?string => DuitkuService::qrisImage($record))
                            ->imageSize('16rem')
                            ->extraImgAttributes(['style' => 'background:#fff;padding:0.75rem;border-radius:0.375rem'])
                            ->placeholder('-')
                            ->visible(fn (Order $record): bool => self::isQris($record))
                            ->columnSpanFull(),
                        TextEntry::make('payment_url')
                            ->label('URL pembayaran')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Supplier (Melo)')
                    ->icon(Heroicon::OutlinedServerStack)
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('supplier_reference')
                            ->label('Referensi supplier')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->placeholder('-'),
                        TextEntry::make('supplier_status')
                            ->label('Status supplier')
                            ->placeholder('-'),
                        TextEntry::make('supplier_message')
                            ->label('Pesan supplier')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('serial_number')
                            ->label('Nomor serial')
                            ->copyable()
                            ->fontFamily(FontFamily::Mono)
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('completed_at')
                            ->label('Selesai pada')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Terakhir diubah')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }

    /**
     * Order QRIS ditampilkan sebagai QR code, bukan teks kode pembayaran.
     */
    private static function isQris(Order $record): bool
    {
        return DuitkuService::channelGroup((string) $record->payment_method) === 'QRIS';
    }
}
