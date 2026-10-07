<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Package;
use App\Support\Catalog;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home()
    {
        $events = Event::with('organizer', 'package')->where('publication', 'published')->where('lifecycle', 'upcoming')->where('registration_opens_at', '<=', now())->where('deadline', '>', now())->latest()->lazy(50)->filter(fn ($event) => $event->isOpen())->take(3)->collect();

        return view('home', ['events' => $events, 'packages' => Package::where('active', true)->get(), 'content' => Catalog::content()]);
    }

    public function events(Request $r)
    {
        $r->validate(['q' => 'nullable|string|max:100', 'city' => 'nullable|string|max:100', 'category' => 'nullable|string|max:100']);
        $q = Event::with('organizer')->where('publication', 'published')->where('lifecycle', '!=', 'cancelled');
        if ($r->filled('q')) {
            $q->where('title', 'like', '%'.$r->q.'%');
        }if ($r->filled('city')) {
            $q->where('city', 'like', '%'.$r->city.'%');
        }if ($r->filled('category')) {
            $q->where('category', $r->category);
        }

        return view('events', ['events' => $q->orderBy('starts_at')->paginate(9)->withQueryString()]);
    }

    public function show(Event $event)
    {
        abort_unless($event->publication === 'published', 404);

        return view('event', ['event' => $event->load('positions', 'organizer')]);
    }

    public function packages()
    {
        return view('packages', ['packages' => Package::where('active', true)->get()]);
    }
}
