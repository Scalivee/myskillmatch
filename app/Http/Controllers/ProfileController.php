<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit(Request $r)
    {
        return view('profile', ['user' => $r->user(), 'skills' => Skill::where('active', true)->orWhereIn('id', $r->user()->skills()->pluck('skills.id'))->orderBy('name')->get()]);
    }

    public function update(Request $r)
    {
        $v = $r->validate(['name' => 'required|string|max:100', 'city' => 'required|string|max:100', 'bio' => 'nullable|string|max:2000', 'skills' => 'nullable|array|max:30', 'skills.*' => 'integer|exists:skills,id', 'slots' => 'nullable|array|max:20', 'slots.*.start' => 'nullable|date', 'slots.*.end' => 'nullable|date']);
        $r->validate(['skills.*' => [Rule::in(Skill::where('active', true)->orWhereIn('id', $r->user()->skills()->pluck('skills.id'))->pluck('id')->all())], 'city' => [Rule::notIn(DB::table('cities')->where('active', false)->where('name', '!=', $r->user()->city ?? '')->pluck('name')->all())]]);
        $slots = [];
        foreach ($v['slots'] ?? [] as $slot) {
            if (empty($slot['start']) && empty($slot['end'])) {
                continue;
            }if (empty($slot['start']) || empty($slot['end']) || strtotime($slot['end']) <= strtotime($slot['start'])) {
                throw ValidationException::withMessages(['slots' => 'Isi awal dan akhir jadwal dengan urutan yang benar.']);
            }$slots[] = ['start' => Carbon::parse($slot['start'], 'Asia/Jakarta')->utc()->toIso8601String(), 'end' => Carbon::parse($slot['end'], 'Asia/Jakarta')->utc()->toIso8601String()];
        }$r->user()->update(['name' => $v['name'], 'city' => trim($v['city']), 'bio' => $v['bio'] ?? null, 'availability' => $slots]);
        $r->user()->skills()->sync($v['skills'] ?? []);

        return back()->with('success', 'Profil berhasil disimpan.');
    }
}
