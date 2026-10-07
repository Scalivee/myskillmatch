<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Catalog
{
    public static function names(string $table): array
    {
        abort_unless(in_array($table, ['categories', 'cities']), 404);

        return DB::table($table)->where('active', true)->orderBy('name')->pluck('name')->all();
    }

    public static function content(): array
    {
        $value = DB::table('settings')->where('key', 'public')->value('value');

        return $value ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : [];
    }
}
