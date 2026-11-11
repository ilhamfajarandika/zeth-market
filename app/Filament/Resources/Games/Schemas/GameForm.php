<?php

namespace App\Filament\Resources\Games\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('publisher'),
                TextInput::make('thumbnail'),
                TextInput::make('banner'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('instruction')
                    ->columnSpanFull(),
                Toggle::make('requires_zone_id')
                    ->required(),
                TextInput::make('user_id_label')
                    ->required()
                    ->default('User ID'),
                TextInput::make('zone_id_label'),
                TextInput::make('user_id_regex'),
                TextInput::make('zone_id_regex'),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_popular')
                    ->required(),
                TextInput::make('sort')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
