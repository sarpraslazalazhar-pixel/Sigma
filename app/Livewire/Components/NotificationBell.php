<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public bool $showDropdown = false;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('notification-sent')]
    #[On('refresh-notifications')]
    public function refreshCount(): void
    {
        $this->unreadCount = Auth::user()?->unreadNotifications()->count() ?? 0;
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
        if ($this->showDropdown) {
            $this->refreshCount();
        }
    }

    public function closeDropdown(): void
    {
        $this->showDropdown = false;
    }

    public function markAsRead(string $id)
    {
        $notification = Auth::user()?->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            $this->refreshCount();

            $data = is_array($notification->data) ? $notification->data : json_decode($notification->data, true);
            if (! empty($data['url'])) {
                $this->showDropdown = false;

                return redirect($data['url']);
            }
        }
    }

    public function markAllAsRead(): void
    {
        Auth::user()?->unreadNotifications()->update(['read_at' => now()]);
        $this->unreadCount = 0;
    }

    public function render()
    {
        $notifications = $this->showDropdown
            ? (Auth::user()?->notifications()->latest()->take(20)->get() ?? collect())
            : collect();

        return view('livewire.components.notification-bell', [
            'notifications' => $notifications,
        ]);
    }
}
