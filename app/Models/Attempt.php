<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['questions' => 'array', 'answers' => 'array', 'started_at' => 'datetime', 'expires_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    protected $hidden = ['questions'];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
