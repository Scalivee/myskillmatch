<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t) {
            $t->dateTime('activated_at')->nullable()->after('paid_at');
        });
        Schema::table('events', function (Blueprint $t) {
            $t->dateTime('activated_at')->nullable()->after('publication');
            $t->string('moderation_note', 500)->nullable()->after('activated_at');
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->json('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn(['activated_at', 'moderation_note']));
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('activated_at'));
    }
};
