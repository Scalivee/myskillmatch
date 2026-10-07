<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Order;
use App\Models\Package;
use App\Models\Position;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'active' => true, 'password' => 'TestPassword123']);
    }

    private function event(User $owner, array $values = []): Event
    {
        return Event::create(array_replace(['organizer_id' => $owner->id, 'package_id' => Package::first()->id, 'title' => 'Kegiatan milik '.$owner->name, 'category' => 'Kampus', 'city' => 'Malang', 'venue' => 'Aula', 'description' => 'Kegiatan untuk pengujian penerimaan revisi aplikasi.', 'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(11), 'registration_opens_at' => now()->subDay(), 'deadline' => now()->addDays(5), 'moderation' => 'approved', 'publication' => 'published', 'package_snapshot' => Package::first()->snapshot()], $values));
    }

    public function test_uat_23_and_30_general_login_rejects_admin_and_ignores_unsafe_intended_url(): void
    {
        $admin = $this->user('admin');
        $this->post('/login', ['email' => $admin->email, 'password' => 'TestPassword123'])->assertSessionHasErrors('email');
        $this->assertGuest('web');
        foreach (['volunteer', 'organizer'] as $role) {
            $u = $this->user($role);
            $this->withSession(['url.intended' => 'https://evil.example/admin/dashboard'])->post('/login', ['email' => $u->email, 'password' => 'TestPassword123'])->assertRedirect('/'.$role.'/aktivitas');
            $this->get('/'.$role.'/aktivitas')->assertOk()->assertSee('Ringkasan Aktivitas')->assertDontSee('Dashboard')->assertDontSee('sidebar');
            $this->get('/'.$role.'/dashboard')->assertRedirect('/'.$role.'/aktivitas');
            $this->post('/logout')->assertRedirect('/');
        }
    }

    public function test_uat_24_and_30_admin_context_and_logout_preserve_general_session(): void
    {
        $vol = $this->user('volunteer');
        $admin = $this->user('admin');
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['email' => $vol->email, 'password' => 'TestPassword123'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $this->post('/login', ['email' => $vol->email, 'password' => 'TestPassword123']);
        $this->withSession(['url.intended' => '/organizer/events'])->post('/admin/login', ['email' => $admin->email, 'password' => 'TestPassword123'])->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($vol, 'web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get('/admin/dashboard')->assertOk()->assertSee('Pengelolaan platform')->assertDontSee('Cara Kerja');
        $this->get('/admin/account')->assertOk()->assertDontSee('Ketersediaan waktu');
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($vol, 'web');
        $this->get('/admin/dashboard')->assertForbidden();
    }

    public function test_uat_25_every_admin_route_rejects_general_users(): void
    {
        $user = $this->user('organizer');
        $event = $this->event($user);
        foreach (['volunteer', 'organizer'] as $role) {
            $this->actingAs($this->user($role), 'web');
            foreach (['dashboard', 'account', 'events', 'users', 'packages', 'logs', 'master', 'settings', 'transactions'] as $page) {
                $this->get('/admin/'.$page)->assertForbidden();
            }
            foreach (['events/'.$event->id.'/review', 'events/'.$event->id.'/publication', 'users/'.$user->id.'/toggle', 'packages/1', 'master', 'settings', 'account', 'skills', 'orders/1/reconcile'] as $action) {
                $this->post('/admin/'.$action)->assertForbidden();
            }
        }
        $admin = $this->user('admin');
        Auth::guard('web')->logout();
        $this->actingAs($admin, 'admin')->get('/organizer/events')->assertRedirect('/login');
    }

    public function test_uat_26_activity_is_scoped_to_signed_in_owner(): void
    {
        $a = $this->user('organizer');
        $b = $this->user('organizer');
        $this->event($a, ['title' => 'Visible own event']);
        $this->event($b, ['title' => 'SECRET OTHER EVENT']);
        $notice = Notice::create(['user_id' => $b->id, 'message' => 'SECRET NOTICE', 'dedupe' => 'other']);
        $this->actingAs($a, 'web')->get('/organizer/aktivitas?user_id='.$b->id)->assertOk()->assertSee('Visible own event')->assertDontSee('SECRET OTHER EVENT')->assertDontSee('SECRET NOTICE');
        $this->post('/notices/'.$notice->id.'/read')->assertForbidden();
        $v = $this->user('volunteer');
        $this->actingAs($v, 'web')->get('/volunteer/aktivitas?user_id='.$b->id)->assertOk()->assertDontSee('SECRET NOTICE')->assertSee('Lengkapi profil');
    }

    public function test_uat_27_period_uses_wib_days_and_current_counts_are_independent(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 05:00:00', 'UTC'));
        $u = $this->user('organizer');
        $u->forceFill(['created_at' => '2026-09-30 17:00:00'])->save();
        $old = $this->user('volunteer');
        $old->forceFill(['created_at' => '2026-09-30 16:59:59'])->save();
        $this->user('admin');
        $event = $this->event($u, ['created_at' => '2026-09-30 17:00:00', 'activated_at' => '2026-09-30 17:00:00']);
        $ended = $this->event($u, ['ends_at' => now()->subHour()]);
        $report = app(AnalyticsService::class)->report(CarbonImmutable::parse('2026-10-01', 'Asia/Jakarta'), CarbonImmutable::parse('2026-10-01', 'Asia/Jakarta'));
        $this->assertSame(2, $report['current']['Total akun Volunteer & Organizer']);
        $this->assertSame(1, $report['current']['Event aktif saat ini']);
        $this->assertSame(1, $report['period']['Akun baru']);
        $this->assertSame(1, $report['series']['Akun baru']['2026-10-01']);
        $this->assertSame(1, $report['compositions']['Aktivasi paket per event dalam periode']['Free']);
        $empty = app(AnalyticsService::class)->report(CarbonImmutable::parse('2020-01-01', 'Asia/Jakarta'), CarbonImmutable::parse('2020-01-02', 'Asia/Jakarta'));
        $this->assertSame($report['current'], $empty['current']);
        $this->assertSame(0, array_sum($empty['period']));
        $this->travelBack();
    }

    public function test_uat_27_load_failure_is_not_reported_as_zero(): void
    {
        $this->mock(AnalyticsService::class)->shouldReceive('report')->once()->andThrow(new QueryException('sqlite', 'select count(*)', [], new \Exception('Unavailable')));
        $this->actingAs($this->user('admin'), 'admin')->get('/admin/dashboard')->assertStatus(503)->assertSee('Analitik gagal dimuat')->assertDontSee('Total akun Volunteer');
    }

    public function test_submission_cohort_uses_submitted_at_instead_of_updated_at_or_creation(): void
    {
        $owner = $this->user('organizer');
        $vol = $this->user('volunteer');
        foreach ([['2026-09-30 17:00:00', 'withdrawn'], ['2026-09-30 16:59:59', 'accepted'], [null, 'draft']] as [$submitted, $status]) {
            $e = $this->event($owner);
            $p = Position::create(['event_id' => $e->id, 'name' => 'Posisi', 'quota' => 5, 'starts_at' => $e->starts_at, 'ends_at' => $e->ends_at, 'skill_ids' => [], 'required_skill_ids' => [], 'questions' => []]);
            Application::create(['event_id' => $e->id, 'position_id' => $p->id, 'volunteer_id' => $vol->id, 'status' => $status, 'submitted_at' => $submitted, 'created_at' => '2026-08-01 00:00:00', 'updated_at' => '2026-10-01 02:00:00']);
        }
        $day = CarbonImmutable::parse('2026-10-01', 'Asia/Jakarta');
        $report = app(AnalyticsService::class)->report($day, $day);
        $this->assertSame(1, $report['period']['Lamaran terkirim']);
        $this->assertSame(['withdrawn' => 1], $report['compositions']['Status terkini lamaran terkirim dalam periode']->all());
        $this->assertSame(1, $report['series']['Lamaran terkirim']['2026-10-01']);
    }

    public function test_inactive_admin_is_rejected_and_general_logout_preserves_admin_context(): void
    {
        $admin = $this->user('admin');
        $vol = $this->user('volunteer');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'TestPassword123']);
        $this->post('/login', ['email' => $vol->email, 'password' => 'TestPassword123']);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($admin, 'admin');
        $admin->forceFill(['active' => false])->save();
        Auth::guard('admin')->setUser($admin->fresh());
        $this->get('/admin/dashboard')->assertForbidden();
        $this->assertGuest('admin');
    }

    public function test_uat_28_replayed_payment_preserves_historical_timestamps_and_total(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 05:00:00', 'UTC'));
        $u = $this->user('organizer');
        $e = $this->event($u, ['publication' => 'unpublished']);
        $order = Order::create(['event_id' => $e->id, 'user_id' => $u->id, 'reference' => 'SM-UAT28', 'amount' => 12345, 'package_snapshot' => array_replace($e->limits(), ['name' => 'Standard', 'price' => 12345])]);
        $data = ['order_id' => $order->reference, 'gross_amount' => 12345, 'transaction_status' => 'settlement'];
        app(PaymentService::class)->apply($order, $data);
        $paidAt = $order->fresh()->paid_at;
        $activatedAt = $e->fresh()->activated_at;
        $this->travel(2)->days();
        app(PaymentService::class)->apply($order, $data);
        $this->assertTrue($paidAt->equalTo($order->fresh()->paid_at));
        $this->assertTrue($activatedAt->equalTo($e->fresh()->activated_at));
        $this->assertTrue($activatedAt->equalTo($order->fresh()->activated_at));
        foreach (['pending', 'failed'] as $status) {
            Order::create(['event_id' => $e->id, 'user_id' => $u->id, 'reference' => 'SM-'.$status, 'amount' => 99999, 'status' => $status, 'package_snapshot' => $e->limits()]);
        }
        $report = app(AnalyticsService::class)->report(CarbonImmutable::parse('2026-10-01', 'Asia/Jakarta'), CarbonImmutable::parse('2026-10-01', 'Asia/Jakarta'));
        $this->assertSame(12345, $report['period']['Nilai transaksi sandbox (Rp)']);
        $this->assertSame(1, $report['compositions']['Aktivasi paket per event dalam periode']['Standard']);
        $this->travelBack();
    }

    public function test_uat_29_moderation_reasons_audit_and_payment_conditions(): void
    {
        $admin = $this->user('admin');
        $owner = $this->user('organizer');
        $e = $this->event($owner);
        $this->actingAs($admin, 'admin')->post('/admin/events/'.$e->id.'/publication', ['action' => 'suspend'])->assertSessionHasErrors('reason');
        $this->post('/admin/events/'.$e->id.'/publication', ['action' => 'suspend', 'reason' => 'Laporan diperiksa'])->assertRedirect();
        $this->assertSame('suspended', $e->fresh()->publication);
        $this->assertDatabaseHas('audit_logs', ['action' => 'event.suspend', 'user_id' => $admin->id]);
        $this->post('/admin/events/'.$e->id.'/publication', ['action' => 'restore', 'reason' => 'Laporan selesai'])->assertRedirect();
        $this->assertSame('published', $e->fresh()->publication);
        $e->update(['publication' => 'suspended', 'package_snapshot' => array_replace($e->limits(), ['price' => 50000])]);
        $this->post('/admin/events/'.$e->id.'/publication', ['action' => 'restore', 'reason' => 'Belum dibayar'])->assertStatus(422);
        $this->assertSame('suspended', $e->fresh()->publication);
    }

    public function test_uat_29_package_edit_preserves_snapshot_and_requires_reason(): void
    {
        $e = $this->event($this->user('organizer'));
        $snapshot = $e->fresh()->package_snapshot;
        $this->actingAs($this->user('admin'), 'admin')->post('/admin/packages/'.$e->package_id, ['price' => 2000, 'positions_limit' => 8, 'applications_limit' => 70, 'days_limit' => 20, 'active' => 0, 'reason' => 'Revisi penawaran'])->assertRedirect();
        $this->assertSame($snapshot, $e->fresh()->package_snapshot);
        $this->assertFalse($e->package->fresh()->active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'package.updated']);
    }

    public function test_admin_reconciliation_uses_gateway_and_never_trusts_form_status(): void
    {
        config(['skillmatch.midtrans_key' => 'sandbox-test']);
        $owner = $this->user('organizer');
        $e = $this->event($owner);
        $o = Order::create(['event_id' => $e->id, 'user_id' => $owner->id, 'reference' => 'SM-ADMIN', 'amount' => 100, 'package_snapshot' => $e->limits()]);
        Http::fake(['*' => Http::response(['order_id' => $o->reference, 'gross_amount' => '100.00', 'transaction_status' => 'pending'])]);
        $this->actingAs($this->user('admin'), 'admin')->post('/admin/orders/'.$o->id.'/reconcile', ['status' => 'paid'])->assertRedirect();
        $this->assertSame('pending', $o->fresh()->status);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.reconcile.completed']);
    }

    public function test_all_admin_modules_render_and_public_content_is_escaped(): void
    {
        $this->actingAs($this->user('admin'), 'admin');
        foreach (['account', 'master', 'transactions', 'settings', 'users', 'events', 'packages', 'logs'] as $page) {
            $this->get('/admin/'.$page)->assertOk();
        }
        $this->post('/admin/settings', ['intro' => '<script>alert(1)</script>', 'service_status' => 'normal', 'reason' => 'Uji escaping'])->assertRedirect();
        $this->get('/')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_inactive_master_is_preserved_and_unavailable_for_new_event(): void
    {
        $id = DB::table('categories')->where('name', 'Kampus')->value('id');
        $this->actingAs($this->user('admin'), 'admin')->post('/admin/master', ['table' => 'categories', 'id' => $id, 'name' => 'Kampus', 'active' => 0, 'reason' => 'Kategori dipensiunkan'])->assertRedirect();
        $this->assertDatabaseHas('categories', ['id' => $id, 'active' => false, 'name' => 'Kampus']);
        $this->actingAs($this->user('organizer'), 'web')->post('/organizer/events', ['category' => 'Kampus'])->assertSessionHasErrors('category');
        $this->assertDatabaseHas('audit_logs', ['action' => 'master.updated']);
    }
}
