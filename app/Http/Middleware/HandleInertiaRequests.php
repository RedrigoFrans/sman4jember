<?php

namespace App\Http\Middleware;

use App\Models\Member;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ] : null,
                'notifications' => $user && $user->role === 'admin' ? [
                    'unreadCount' => \App\Models\AdminNotification::where('is_read', false)->count(),
                    'latest'      => \App\Models\AdminNotification::where('is_read', false)->latest()->take(5)->get(),
                ] : null,
            ],
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
                'warning' => session('warning'),
                'info'    => session('info'),
                'reset_token' => session('reset_token'),
                'otp_sent'   => session('otp_sent'),
                'otp_email'  => session('otp_email'),
                'phone_hint' => session('phone_hint'),
                'email_hint' => session('email_hint'),
            ],
        ];
    }
}
