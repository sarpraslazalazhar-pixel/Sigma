<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Masuk: SIGMA LAZ Al-Azhar')]
class Login extends Component
{
    public string $login_id = '';

    public string $password = '';

    public bool $remember = false;

    public bool $show_password = false;

    protected function rules(): array
    {
        return [
            'login_id' => ['required', 'string', 'min:3'],
            'password' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'login_id.required' => 'Username atau email wajib diisi.',
            'login_id.min' => 'Username atau email minimal 3 karakter.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    public function togglePassword(): void
    {
        $this->show_password = ! $this->show_password;
    }

    public function fillDemo(string $username): void
    {
        $this->login_id = $username;
        $this->password = 'password';
        $this->resetErrorBag();
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->login_id).'|'.request()->ip());
    }

    protected function ensureIsNotRateLimited(): void
    {
        $key = $this->throttleKey();

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'login_id' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }

    public function login(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        $identifier = trim($this->login_id);
        $fieldType = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($fieldType, $identifier)->first();

        if ($user && ! $user->is_active) {
            RateLimiter::hit($this->throttleKey(), 60);
            throw ValidationException::withMessages([
                'login_id' => 'Akun Anda dinonaktifkan. Hubungi Administrator IT untuk informasi lebih lanjut.',
            ]);
        }

        $credentials = [
            $fieldType => $identifier,
            'password' => $this->password,
        ];

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($this->throttleKey(), 60);
            throw ValidationException::withMessages([
                'login_id' => 'Kombinasi username atau email dan kata sandi tidak cocok.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirectIntended(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
