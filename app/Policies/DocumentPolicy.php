<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $u, Document $d): bool
    {
        if (! $u->active) {
            return false;
        }$a = $d->application;
        if ($a->volunteer_id === $u->id) {
            return true;
        }

        return $u->role === 'organizer' && $a->submitted_at && $a->status !== 'withdrawn' && $a->event->organizer_id === $u->id && $a->event->ends_at->addDays(90)->isFuture();
    }

    public function download(User $u, Document $d): bool
    {
        return $d->status === 'ready' && $this->view($u, $d);
    }
}
