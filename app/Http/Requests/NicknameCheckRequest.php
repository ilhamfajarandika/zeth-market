<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesGameIdPattern;
use App\Models\Game;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Cek nickname pelanggan (PROJECT_SPEC P11). Hanya ID yang diterima; game dan kodenya dibaca dari route.
 */
class NicknameCheckRequest extends FormRequest
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
            'target_user_id' => ['required', 'string', 'max:50', ...$this->patternRule($game->user_id_regex)],
            'target_zone_id' => $game->requires_zone_id
                ? ['required', 'string', 'max:20', ...$this->patternRule($game->zone_id_regex)]
                : ['nullable'],
        ];
    }
}
