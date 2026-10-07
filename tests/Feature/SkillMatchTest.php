<?php

namespace Tests\Feature;

use App\Jobs\ScanDocument;
use App\Models\Application;
use App\Models\Attempt;
use App\Models\Document;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Order;
use App\Models\Package;
use App\Models\Position;
use App\Models\Skill;
use App\Models\User;
use App\Services\MatchingService;
use App\Services\PaymentService;
use App\Services\RecruitmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SkillMatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $vol;

    protected User $org;

    protected User $admin;

    protected Event $event;

    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        $this->vol = $this->person('volunteer');
        $this->org = $this->person('organizer');
        $this->admin = $this->person('admin');
        $this->event = Event::create(['organizer_id' => $this->org->id, 'package_id' => Package::first()->id, 'title' => 'Event Uji', 'category' => 'Kampus', 'city' => 'Malang', 'venue' => 'Gedung Uji', 'description' => 'Deskripsi event uji yang panjang untuk memeriksa rekrutmen.', 'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHours(8), 'registration_opens_at' => now()->subDay(), 'deadline' => now()->addDays(5), 'moderation' => 'approved', 'publication' => 'published', 'package_snapshot' => Package::first()->snapshot()]);
        $this->position = Position::create(['event_id' => $this->event->id, 'name' => 'Registrasi', 'quota' => 1, 'starts_at' => $this->event->starts_at, 'ends_at' => $this->event->ends_at, 'skill_ids' => Skill::limit(3)->pluck('id')->all(), 'required_skill_ids' => [], 'cv_required' => false, 'questions' => [['text' => 'Apa jawaban benar?', 'options' => ['A', 'B', 'C', 'D'], 'answer' => 1]], 'duration_minutes' => 10]);
    }

    protected function person(string $role): User
    {
        $u = User::factory()->create(['role' => $role, 'active' => true, 'city' => 'Malang', 'availability' => [['start' => now()->toIso8601String(), 'end' => now()->addMonth()->toIso8601String()]]]);
        $u->skills()->sync(Skill::limit(3)->pluck('id'));

        return $u;
    }

    protected function application(string $status = 'draft', ?User $user = null): Application
    {
        return Application::create(['volunteer_id' => ($user ?? $this->vol)->id, 'event_id' => $this->event->id, 'position_id' => $this->position->id, 'status' => $status, 'submitted_at' => $status === 'draft' ? null : now(), 'snapshot' => ['name' => 'Test', 'bio' => '', 'city' => 'Malang', 'skills' => Skill::limit(3)->pluck('id')->all(), 'availability' => $this->vol->availability]]);
    }

    public function test_public_pages_and_role_pages_render(): void
    {
        foreach (['/', '/events', '/packages', '/login', '/register', '/forgot-password', '/events/'.$this->event->id] as $url) {
            $this->get($url)->assertOk();
        }
        foreach ([[$this->vol, ['/volunteer/aktivitas', '/volunteer/profile', '/volunteer/applications', '/volunteer/history']], [$this->org, ['/organizer/aktivitas', '/organizer/events', '/organizer/events/create', '/organizer/events/'.$this->event->id, '/organizer/events/'.$this->event->id.'/applicants', '/organizer/events/'.$this->event->id.'/attendance']], [$this->admin, ['/admin/dashboard', '/admin/events', '/admin/users', '/admin/packages', '/admin/logs']]] as [$user,$urls]) {
            foreach ($urls as $url) {
                $this->actingAs($user, $user->role === 'admin' ? 'admin' : 'web')->get($url)->assertOk();
            }
        }
    }

    public function test_role_and_event_owner_are_enforced(): void
    {
        $this->actingAs($this->vol)->get('/admin/dashboard')->assertForbidden();
        $other = $this->person('organizer');
        $this->actingAs($other)->get('/organizer/events/'.$this->event->id)->assertForbidden();
    }

    public function test_registration_cannot_create_admin(): void
    {
        Notification::fake();
        $this->post('/register', ['name' => 'Attacker', 'email' => 'fake@example.test', 'password' => 'LongPassword123', 'password_confirmation' => 'LongPassword123', 'role' => 'admin'])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'fake@example.test']);
    }

    public function test_matching_merges_overlapping_intervals(): void
    {
        $start = '2026-10-10T00:00:00Z';
        $end = '2026-10-10T08:00:00Z';
        $score = app(MatchingService::class)->scores(['skills' => [1, 2, 3], 'city' => 'Malang', 'availability' => [['start' => $start, 'end' => '2026-10-10T04:00:00Z'], ['start' => '2026-10-10T02:00:00Z', 'end' => '2026-10-10T06:00:00Z']]], ['skill_ids' => [1, 2, 3, 4], 'starts_at' => $start, 'ends_at' => $end], 'Malang');
        $this->assertEquals(80, $score['match_score']);
        $this->assertEquals(75, $score['availability_score']);
    }

    public function test_application_assessment_and_selection_flow(): void
    {
        $this->actingAs($this->vol)->post('/positions/'.$this->position->id.'/apply')->assertRedirect();
        $a = Application::first();
        $this->post('/applications/'.$a->id.'/submit')->assertRedirect();
        $this->assertEquals('assessment_pending', $a->fresh()->status);
        $this->post('/applications/'.$a->id.'/assessment/start')->assertRedirect();
        $this->get('/applications/'.$a->id.'/assessment')->assertOk();
        $this->post('/applications/'.$a->id.'/assessment', ['answers' => [1], 'finish' => 1])->assertRedirect();
        $this->assertEquals('under_review', $a->fresh()->status);
        $this->assertEquals(100, $a->fresh()->assessment_score);
        $this->actingAs($this->org)->post('/organizer/applications/'.$a->id.'/decision', ['decision' => 'accepted'])->assertRedirect();
        $this->assertEquals('accepted', $a->fresh()->status);
        $this->get('/applications/'.$a->id)->assertOk();
    }

    public function test_duplicate_application_and_submit_do_not_duplicate(): void
    {
        $this->actingAs($this->vol);
        for ($i = 0; $i < 2; $i++) {
            $this->post('/positions/'.$this->position->id.'/apply');
        }$a = Application::first();
        for ($i = 0; $i < 2; $i++) {
            $this->post('/applications/'.$a->id.'/submit');
        }$this->assertEquals(1, Application::count());
        $this->assertEquals(1, Notice::where('dedupe', 'submitted-'.$a->id)->count());
    }

    public function test_required_skill_fails_screening(): void
    {
        $this->position->update(['required_skill_ids' => [Skill::latest('id')->first()->id]]);
        $a = $this->application();
        app(RecruitmentService::class)->submit($a);
        $this->assertEquals('screening_failed', $a->fresh()->status);
    }

    public function test_capacity_prevents_second_acceptance(): void
    {
        $a = $this->application('accepted');
        $b = $this->application('under_review', $this->person('volunteer'));
        $this->actingAs($this->org)->post('/organizer/applications/'.$b->id.'/decision', ['decision' => 'accepted'])->assertSessionHasErrors('quota');
        $this->assertEquals('under_review', $b->fresh()->status);
    }

    public function test_late_assessment_cannot_replace_saved_answers(): void
    {
        $a = $this->application('assessment_pending');
        $t = Attempt::create(['application_id' => $a->id, 'questions' => $this->position->questions, 'answers' => [0], 'started_at' => now()->subMinutes(20), 'expires_at' => now()->subMinute()]);
        $this->actingAs($this->vol)->post('/applications/'.$a->id.'/assessment', ['answers' => [1], 'finish' => 1])->assertRedirect();
        $this->assertEquals(0, $a->fresh()->assessment_score);
    }

    public function test_document_authorization_and_withdrawal(): void
    {
        Storage::fake('private');
        $a = $this->application('under_review');
        $path = 'quarantine/file.pdf';
        Storage::disk('private')->put($path, '%PDF-1.4 example');
        $d = Document::create(['id' => (string) Str::uuid(), 'application_id' => $a->id, 'uploader_id' => $this->vol->id, 'kind' => 'cv', 'path' => $path, 'original_name' => 'CV.pdf', 'mime' => 'application/pdf', 'size' => 16, 'checksum' => str_repeat('a', 64), 'status' => 'ready']);
        $this->actingAs($this->org)->get('/documents/'.$d->id.'/download')->assertOk();
        $this->actingAs($this->person('organizer'))->get('/documents/'.$d->id.'/download')->assertForbidden();
        $this->actingAs($this->admin)->get('/documents/'.$d->id.'/download')->assertForbidden();
        $a->update(['status' => 'withdrawn']);
        $this->actingAs($this->org)->get('/documents/'.$d->id.'/download')->assertForbidden();
    }

    public function test_upload_is_private_and_scanner_fails_closed(): void
    {
        Storage::fake('private');
        Queue::fake();
        $a = $this->application();
        $pdf = UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $this->actingAs($this->vol)->post('/applications/'.$a->id.'/documents', ['kind' => 'cv', 'file' => $pdf])->assertSessionHasNoErrors();
        $d = Document::firstOrFail();
        $this->assertEquals('quarantined', $d->status);
        $this->get('/documents/'.$d->id.'/download')->assertForbidden();
        config(['skillmatch.scanner' => 'disabled']);
        (new ScanDocument($d->id))->handle();
        $this->assertEquals('scan_failed', $d->fresh()->status);
        $this->get('/documents/'.$d->id.'/download')->assertForbidden();
        Queue::assertPushed(ScanDocument::class);
    }

    public function test_executable_disguised_as_pdf_is_rejected(): void
    {
        Storage::fake('private');
        $a = $this->application();
        $this->actingAs($this->vol)->post('/applications/'.$a->id.'/documents', ['kind' => 'cv', 'file' => UploadedFile::fake()->createWithContent('evil.pdf', '<?php echo 1;')])->assertSessionHasErrors('file');
        $this->assertEquals(0, Document::count());
    }

    public function test_payment_is_idempotent_and_does_not_regress(): void
    {
        $this->event->update(['publication' => 'unpublished']);
        $o = Order::create(['event_id' => $this->event->id, 'user_id' => $this->org->id, 'reference' => 'SM-test', 'amount' => 10000, 'package_snapshot' => $this->event->limits()]);
        $data = ['order_id' => 'SM-test', 'gross_amount' => '10000.00', 'transaction_status' => 'settlement'];
        $service = app(PaymentService::class);
        $service->apply($o, $data);
        $service->apply($o, $data);
        $service->apply($o, ['order_id' => 'SM-test', 'gross_amount' => '10000.00', 'transaction_status' => 'pending']);
        $this->assertEquals('paid', $o->fresh()->status);
        $this->assertEquals('published', $this->event->fresh()->publication);
        $this->assertEquals(1, Notice::where('dedupe', 'paid-'.$o->id)->count());
    }

    public function test_checkout_redirect_allows_midtrans_and_reuses_pending_order(): void
    {
        config(['skillmatch.midtrans_key' => 'sandbox-test-key']);
        $this->event->update(['publication' => 'unpublished', 'package_snapshot' => array_merge($this->event->limits(), ['price' => 30000])]);
        $checkout = 'https://app.sandbox.midtrans.com/snap/v2/vtweb/test-token';
        Http::preventStrayRequests();
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['redirect_url' => $checkout], 201)]);
        $page = $this->actingAs($this->org)->get(route('organizer.event', $this->event))->assertOk();
        $this->assertStringContainsString("form-action 'self' https://app.sandbox.midtrans.com", $page->headers->get('Content-Security-Policy'));
        for ($i = 0; $i < 2; $i++) {
            $response = $this->post(route('organizer.pay', $this->event))->assertStatus(303)->assertRedirect($checkout);
            $this->assertStringContainsString("form-action 'self' https://app.sandbox.midtrans.com", $response->headers->get('Content-Security-Policy'));
        }
        $this->assertDatabaseCount('orders', 1);
        Http::assertSentCount(1);
        $login = $this->get('/');
        $this->assertStringNotContainsString('app.sandbox.midtrans.com', $login->headers->get('Content-Security-Policy'));
    }

    public function test_payment_status_errors_explain_authentication_and_unselected_method(): void
    {
        config(['skillmatch.midtrans_key' => 'sandbox-test-key']);
        $this->event->update(['publication' => 'unpublished']);
        $order = Order::create(['event_id' => $this->event->id, 'user_id' => $this->org->id, 'reference' => 'SM-status-test', 'amount' => 30000, 'package_snapshot' => $this->event->limits()]);
        Http::preventStrayRequests();
        Http::fake(['api.sandbox.midtrans.com/*' => Http::sequence()
            ->push(['status_code' => '401'], 401)
            ->push(['status_code' => '404'], 404)]);
        $url = route('organizer.event', $this->event);
        $this->actingAs($this->org)->from($url)->post(route('organizer.reconcile', $order))
            ->assertRedirect($url)->assertSessionHasErrors(['payment' => 'Kredensial Midtrans ditolak. Gunakan Server Key Sandbox dari akun yang membuat transaksi, bukan key Production atau Client Key.']);
        $this->from($url)->post(route('organizer.reconcile', $order))
            ->assertRedirect($url)->assertSessionHasErrors(['payment' => 'Transaksi belum ditemukan di Midtrans. Jika belum memilih metode pembayaran, klik Bayar Paket pada halaman event dan lanjutkan di Midtrans. Pastikan Server Key Sandbox berasal dari akun yang membuat transaksi.']);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('unpublished', $this->event->fresh()->publication);
    }

    public function test_bad_signature_and_amount_do_not_activate_event(): void
    {
        config(['skillmatch.midtrans_key' => 'sandbox-test-key']);
        $this->postJson('/payments/midtrans/notification', ['order_id' => 'SM-test', 'status_code' => '200', 'gross_amount' => '100', 'signature_key' => 'wrong'])->assertForbidden();
    }

    public function test_admin_toggle_actually_disables_account(): void
    {
        $this->actingAs($this->admin, 'admin')->post('/admin/users/'.$this->vol->id.'/toggle', ['reason' => 'Akun uji'])->assertRedirect();
        $this->assertFalse($this->vol->fresh()->active);
        $this->actingAs($this->vol->fresh(), 'web')->get('/volunteer/dashboard')->assertForbidden();
    }

    public function test_unverified_volunteer_cannot_apply(): void
    {
        $this->vol->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($this->vol)->post('/positions/'.$this->position->id.'/apply')->assertRedirect('/email/verify');
    }
}
