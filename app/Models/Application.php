<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'screening' => 'array', 'submitted_at' => 'datetime'];
    }

    public function volunteer()
    {
        return $this->belongsTo(User::class, 'volunteer_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function attempt()
    {
        return $this->hasOne(Attempt::class);
    }
}
