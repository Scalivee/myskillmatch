<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skills', fn (Blueprint $t) => $t->boolean('active')->default(true));
        foreach (['categories', 'cities'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name', 100)->unique();
                $t->boolean('active')->default(true);
                $t->timestamps();
            });
        }
        $now = now();
        foreach (array_unique(array_merge(config('skillmatch.categories'), DB::table('events')->distinct()->pluck('category')->all())) as $name) {
            DB::table('categories')->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach (DB::table('events')->pluck('city')->merge(DB::table('users')->whereNotNull('city')->pluck('city'))->filter()->unique() as $name) {
            DB::table('cities')->insert(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }
        Schema::table('applications', fn (Blueprint $t) => $t->index('submitted_at'));
        Schema::table('orders', fn (Blueprint $t) => $t->index(['status', 'paid_at']));
        Schema::table('events', fn (Blueprint $t) => $t->index('activated_at'));
    }

    public function down(): void
    {
        Schema::table('applications', fn (Blueprint $t) => $t->dropIndex(['submitted_at']));
        Schema::table('orders', fn (Blueprint $t) => $t->dropIndex(['status', 'paid_at']));
        Schema::table('events', fn (Blueprint $t) => $t->dropIndex(['activated_at']));
        Schema::table('skills', fn (Blueprint $t) => $t->dropColumn('active'));
        Schema::dropIfExists('categories');
        Schema::dropIfExists('cities');
    }
};
