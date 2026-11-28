<?php

namespace App\Http\Requests\Concerns;

trait ValidatesGameIdPattern
{
    /**
     * Pola regex game disimpan tanpa delimiter (lihat GameForm::validRegex).
     *
     * @return list<string>
     */
    protected function patternRule(?string $pattern): array
    {
        return filled($pattern) ? ['regex:/'.str_replace('/', '\/', $pattern).'/'] : [];
    }
}
