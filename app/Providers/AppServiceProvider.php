<?php

namespace App\Providers;

use App\Models\KodeAnggaran;
use App\Models\Ump;
use App\Models\User;
use App\Policies\KodeAnggaranPolicy;
use App\Policies\PermohonanPolicy;
use App\Policies\UmpPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Model Policies
        Gate::policy(Permohonan::class, PermohonanPolicy::class);
        Gate::policy(Ump::class, UmpPolicy::class);
        Gate::policy(KodeAnggaran::class, KodeAnggaranPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // 2. Super-admin bypass: Admin memiliki semua izin secara otomatis
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->isAdmin()) {
                return true;
            }

            return null;
        });

        // 3. RBAC Application Gates
        Gate::define('manage-users', fn (User $user) => $user->isAdmin());
        Gate::define('manage-master-data', fn (User $user) => $user->isAdmin());
        Gate::define('view-audit-logs', fn (User $user) => $user->hasRole(['admin', 'keuangan']));
        Gate::define('manage-budget', fn (User $user) => $user->hasRole(['admin', 'keuangan']));
        Gate::define('verifikasi-keuangan', fn (User $user) => $user->hasRole(['admin', 'keuangan']) || (bool) $user->is_petugas_input);
        Gate::define('cairkan-dana', fn (User $user) => $user->hasRole(['admin', 'keuangan']) || (bool) $user->is_petugas_input);
        Gate::define('create-permohonan', fn (User $user) => ! $user->isViewer());

        // 4. Custom Blade Directives for RBAC
        Blade::if('role', function (string|array $roles): bool {
            $user = auth()->user();

            return $user ? $user->hasRole($roles) : false;
        });

        Blade::if('permission', function (string $permission): bool {
            $user = auth()->user();

            return $user ? $user->can($permission) : false;
        });
    }
}
