<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Document;
use App\Models\Event;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function report(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $bounds = [$start->utc(), $end->endOfDay()->utc()];
        $accounts = User::whereIn('role', ['volunteer', 'organizer']);
        $events = Event::whereBetween('created_at', $bounds);
        $apps = Application::whereBetween('submitted_at', $bounds);
        $paid = Order::where('status', 'paid')->whereBetween('paid_at', $bounds);
        $series = [];
        foreach (['Akun baru' => [clone $accounts, 'created_at', 'COUNT(*)'], 'Event dibuat' => [Event::query(), 'created_at', 'COUNT(*)'], 'Lamaran terkirim' => [Application::query(), 'submitted_at', 'COUNT(*)'], 'Nilai transaksi sandbox (Rp)' => [Order::where('status', 'paid'), 'paid_at', 'SUM(amount)']] as $label => [$query, $column, $aggregate]) {
            // Stored timestamps are UTC; group dates by the displayed WIB day.
            $date = DB::getDriverName() === 'sqlite' ? "date($column, '+7 hours')" : "DATE(DATE_ADD($column, INTERVAL 7 HOUR))";
            $values = $query->whereBetween($column, $bounds)->selectRaw("$date as day, $aggregate as total")->groupByRaw($date)->pluck('total', 'day');
            $series[$label] = [];
            for ($day = $start; $day->lte($end); $day = $day->addDay()) {
                $series[$label][$day->format('Y-m-d')] = (int) ($values[$day->format('Y-m-d')] ?? 0);
            }
        }
        $packages = Event::whereBetween('activated_at', $bounds)->get(['package_snapshot'])->groupBy(fn ($e) => $e->package_snapshot['name'] ?? 'Tidak tersedia')->map->count();

        return [
            'current' => ['Total akun Volunteer & Organizer' => (clone $accounts)->count(), 'Akun Admin (terpisah)' => User::where('role', 'admin')->count(), 'Event aktif saat ini' => Event::where('publication', 'published')->whereNotIn('lifecycle', ['cancelled', 'completed'])->where('ends_at', '>=', now())->count()],
            'period' => ['Akun baru' => (clone $accounts)->whereBetween('created_at', $bounds)->count(), 'Event dibuat' => (clone $events)->count(), 'Lamaran terkirim' => (clone $apps)->count(), 'Nilai transaksi sandbox (Rp)' => (int) (clone $paid)->sum('amount')],
            'series' => $series,
            'compositions' => ['Akun saat ini menurut role & status' => (clone $accounts)->selectRaw('role, active, COUNT(*) as total')->groupBy('role', 'active')->get()->mapWithKeys(fn ($u) => [$u->role.' · '.($u->active ? 'Aktif' : 'Nonaktif') => $u->total]), 'Status terkini event yang dibuat dalam periode' => $events->selectRaw('moderation, COUNT(*) as total')->groupBy('moderation')->pluck('total', 'moderation'), 'Status terkini lamaran terkirim dalam periode' => $apps->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'), 'Aktivasi paket per event dalam periode' => $packages],
            'queue' => ['Event menunggu verifikasi' => Event::where('moderation', 'pending_review')->count(), 'Pemindaian dokumen gagal' => Document::where('status', 'scan_failed')->count(), 'Transaksi menunggu pemeriksaan' => Order::where('status', 'pending')->count()],
            'unknownActivations' => Event::whereNull('activated_at')->where('publication', 'published')->count(),
            'unknownPayments' => Order::where('status', 'paid')->whereNull('paid_at')->count(),
            'computedAt' => now()->timezone('Asia/Jakarta'),
        ];
    }
}
