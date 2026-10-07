<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['activated_at' => 'datetime', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'registration_opens_at' => 'datetime', 'deadline' => 'datetime', 'package_snapshot' => 'array'];
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function limits(): array
    {
        return $this->package_snapshot ?? $this->package->snapshot();
    }

    public function isOpen(): bool
    {
        return $this->publication === 'published' && $this->lifecycle === 'upcoming' && now()->between($this->registration_opens_at, $this->deadline) && $this->applications()->whereNotNull('submitted_at')->count() < $this->limits()['applications_limit'];
    }
}
