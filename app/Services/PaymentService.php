<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Order;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function key(): string
    {
        $key = config('skillmatch.midtrans_key');
        if (! $key) {
            throw ValidationException::withMessages(['payment' => 'Isi MIDTRANS_SERVER_KEY sandbox untuk mengaktifkan pembayaran.']);
        }

        return $key;
    }

    public function checkout(Order $o): string
    {
        if ($o->checkout_url) {
            return $o->checkout_url;
        }
        $r = Http::timeout(20)->withBasicAuth($this->key(), '')->post('https://app.sandbox.midtrans.com/snap/v1/transactions', ['transaction_details' => ['order_id' => $o->reference, 'gross_amount' => $o->amount], 'customer_details' => ['first_name' => $o->user->name, 'email' => $o->user->email], 'callbacks' => ['finish' => route('organizer.event', $o->event_id)]]);
        if (! $r->successful()) {
            if ($r->status() === 401 || (string) $r->json('status_code') === '401') {
                throw ValidationException::withMessages(['payment' => 'Kredensial Midtrans ditolak. Gunakan Server Key Sandbox dari akun yang membuat transaksi, bukan key Production atau Client Key.']);
            }
            throw ValidationException::withMessages(['payment' => 'Gateway belum dapat membuat sesi. Gunakan Periksa Status sebelum mencoba lagi.']);
        }
        $url = $r->json('redirect_url');
        abort_unless(is_string($url) && parse_url($url, PHP_URL_SCHEME) === 'https' && parse_url($url, PHP_URL_HOST) === 'app.sandbox.midtrans.com', 502);
        $o->update(['checkout_url' => $url]);

        return $url;
    }

    public function reconcile(Order $o): void
    {
        $r = Http::timeout(20)->withBasicAuth($this->key(), '')->get('https://api.sandbox.midtrans.com/v2/'.rawurlencode($o->reference).'/status');
        if ($r->status() === 401 || (string) $r->json('status_code') === '401') {
            throw ValidationException::withMessages(['payment' => 'Kredensial Midtrans ditolak. Gunakan Server Key Sandbox dari akun yang membuat transaksi, bukan key Production atau Client Key.']);
        }
        if ($r->status() === 404 || (string) $r->json('status_code') === '404') {
            throw ValidationException::withMessages(['payment' => 'Transaksi belum ditemukan di Midtrans. Jika belum memilih metode pembayaran, klik Bayar Paket pada halaman event dan lanjutkan di Midtrans. Pastikan Server Key Sandbox berasal dari akun yang membuat transaksi.']);
        }
        if (! $r->successful()) {
            throw ValidationException::withMessages(['payment' => 'Transaksi belum ditemukan atau gateway tidak tersedia. Jangan membuat pembayaran ganda.']);
        }
        $this->apply($o, $r->json());
    }

    public function apply(Order $order, array $data): void
    {
        abort_unless(($data['order_id'] ?? '') === $order->reference && isset($data['gross_amount']) && is_numeric($data['gross_amount']) && abs((float) $data['gross_amount'] - $order->amount) < 0.001, 422);
        DB::transaction(function () use ($order, $data) {
            $event = Event::lockForUpdate()->findOrFail($order->event_id);
            $o = Order::lockForUpdate()->findOrFail($order->id);
            if ($o->status === 'paid') {
                return;
            }
            $s = $data['transaction_status'] ?? '';
            $fraud = $data['fraud_status'] ?? '';
            $state = match (true) {
                $s === 'settlement' || ($s === 'capture' && $fraud === 'accept') => 'paid',$s === 'expire' => 'expired',in_array($s, ['cancel', 'deny', 'failure']) => 'failed',default => 'pending'
            };
            if ($state === 'pending' && $o->status !== 'pending') {
                return;
            }
            $o->update(['status' => $state, 'paid_at' => $state === 'paid' ? now() : null]);
            if ($state === 'paid') {
                if ($event->moderation === 'approved' && $event->lifecycle === 'upcoming' && $event->deadline->isFuture() && $event->publication !== 'suspended') {
                    $event->update(['publication' => 'published', 'package_snapshot' => $o->package_snapshot, 'activated_at' => $event->activated_at ?? now()]);
                    $o->update(['activated_at' => $o->activated_at ?? now()]);
                }
                Activity::notify($o->user_id, 'Pembayaran paket terverifikasi. Periksa status event.', 'paid-'.$o->id);
                Activity::log('payment.paid', (string) $o->id);
            }
        });
    }
}
