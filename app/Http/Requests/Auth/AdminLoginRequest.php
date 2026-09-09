<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AdminLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:dns,rfc'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean']
        ];
    }

    public function ensureIsNotRateLimited(): void
    {
        if(!RateLimiter::tooManyAttempts($this->throttleKey(), 5)){
            return;
        }
        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function clearRateLimit(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    public function hitRateLimit(): void
    {
        RateLimiter::hit($this->throttleKey(), 300);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email') . '|' . $this->ip()));
    }
}
