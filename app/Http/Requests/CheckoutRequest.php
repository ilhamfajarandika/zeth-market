<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesGameIdPattern;
use App\Models\Game;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checkout guest/pelanggan untuk produk top up. Harga, fee, dan total tidak diterima dari input;
 * dihitung di CheckoutController.
 */
class CheckoutRequest extends FormRequest
{
    use ValidatesGameIdPattern;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Game $game */
        $game = $this->route('game');

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('game_id', $game->id)
                    ->where('is_active', true),
            ],
            'target_user_id' => ['required', 'string', 'max:50', ...$this->patternRule($game->user_id_regex)],
            'target_zone_id' => $game->requires_zone_id
                ? ['required', 'string', 'max:20', ...$this->patternRule($game->zone_id_regex)]
                : ['prohibited'],
            'whatsapp' => ['required', 'string', 'regex:/^(\+62|62|0)8\d{7,12}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'payment_method' => ['required', 'string', 'max:10'],
            'id_confirmed' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $game = $this->route('game');

        return [
            'product_id.required' => 'Pilih item terlebih dahulu.',
            'product_id.exists' => 'Item tidak tersedia. Pilih item lain.',
            'target_user_id.required' => "{$game->user_id_label} wajib diisi.",
            'target_user_id.regex' => "Format {$game->user_id_label} tidak valid.",
            'target_zone_id.required' => "{$game->zone_id_label} wajib diisi.",
            'target_zone_id.regex' => "Format {$game->zone_id_label} tidak valid.",
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.',
            'email.email' => 'Format email tidak valid.',
            'payment_method.required' => 'Pilih metode pembayaran.',
            'id_confirmed.accepted' => 'Centang konfirmasi bahwa ID sudah benar.',
            '*.max' => 'Isian terlalu panjang.',
        ];
    }
}
