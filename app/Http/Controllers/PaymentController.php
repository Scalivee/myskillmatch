<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function notification(Request $r, PaymentService $service)
    {
        $v = $r->validate(['order_id' => 'required|string', 'status_code' => 'required|string', 'gross_amount' => 'required|string', 'signature_key' => 'required|string']);
        $expected = hash('sha512', $v['order_id'].$v['status_code'].$v['gross_amount'].$service->key());
        abort_unless(hash_equals($expected, $v['signature_key']), 403);
        $o = Order::where('reference', $v['order_id'])->firstOrFail();
        $service->reconcile($o);

        return response()->json(['ok' => true]);
    }
}
