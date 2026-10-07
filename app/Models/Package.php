<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function snapshot(): array
    {
        return $this->only(['name', 'price', 'positions_limit', 'applications_limit', 'days_limit']);
    }
}
