@foreach(['dashboard'=>'Dashboard','users'=>'Pengguna','events'=>'Event & Moderasi','master'=>'Master Data','packages'=>'Paket','transactions'=>'Transaksi Sandbox','settings'=>'Konten & Pengaturan','logs'=>'Audit & Operasional','account'=>'Akun & Keamanan'] as $route=>$label)
<a href="{{ route('admin.'.$route) }}" @if(request()->routeIs('admin.'.$route)) class="active" aria-current="page" @endif>{{ $label }}</a>
@endforeach
<form method="post" action="{{ route('admin.logout') }}">@csrf<button class="btn secondary">Keluar Admin</button></form>
