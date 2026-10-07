<?php

namespace App\Http\Requests;

use App\Support\Catalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'organizer';
    }

    public function rules(): array
    {
        return ['title' => 'required|string|max:160', 'category' => ['required', Rule::in(Catalog::names('categories'))], 'city' => ['required', 'string', 'max:100', Rule::notIn(DB::table('cities')->where('active', false)->pluck('name')->all())], 'venue' => 'required|string|max:200', 'description' => 'required|string|min:30|max:10000', 'package_id' => 'required|integer|exists:packages,id', 'starts_at' => 'required|date|after:now', 'ends_at' => 'required|date|after:starts_at', 'registration_opens_at' => 'required|date|before:deadline', 'deadline' => 'required|date|after:now|before:starts_at'];
    }
}
