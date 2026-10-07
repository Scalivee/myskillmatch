@php($role=auth('web')->user()->role)
@if(in_array($role,['volunteer','organizer']))
<a class="{{ request()->routeIs($role.'.activity')?'active':'' }}" href="{{ route($role.'.activity') }}">Aktivitas Saya</a>
@if($role==='volunteer')<a href="{{ route('volunteer.applications') }}">Lamaran Saya</a><a href="{{ route('volunteer.history') }}">Riwayat Partisipasi</a>@else<a href="{{ route('organizer.events') }}">Event Saya</a><a href="{{ route('organizer.transactions') }}">Transaksi Saya</a>@endif
<a href="{{ route($role.'.activity') }}#notifikasi">Notifikasi</a><a href="{{ route($role.'.profile') }}">{{ $role==='organizer'?'Profil Organisasi':'Profil & Skill' }}</a>
@endif
<form method="post" action="{{ route('logout') }}">@csrf<button class="text-button">Keluar</button></form>
