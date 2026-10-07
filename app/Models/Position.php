<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'skill_ids' => 'array', 'required_skill_ids' => 'array', 'questions' => 'array', 'full_availability' => 'boolean', 'same_city' => 'boolean', 'cv_required' => 'boolean'];
    }

    protected $hidden = ['questions'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
