<?php

namespace App\Models;

use Database\Factories\ApiLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provider', 'direction', 'reference', 'endpoint', 'request', 'response', 'signature_valid', 'http_status'])]
class ApiLog extends Model
{
    /** @use HasFactory<ApiLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request' => 'array',
            'response' => 'array',
            'signature_valid' => 'boolean',
        ];
    }
}
