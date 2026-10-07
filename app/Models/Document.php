<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [];
    }

    public $incrementing = false;

    protected $keyType = 'string';

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
