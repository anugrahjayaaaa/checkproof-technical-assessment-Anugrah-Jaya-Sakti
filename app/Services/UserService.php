<?php

namespace App\Services;

use App\Mail\AdminNewUserNotificationMail;
use App\Mail\UserCreateMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserService
{
    public function create(array $data): User
    {
        $user = User::create($data);

        try {
            // Send email to user
            Mail::to($user->email)
                ->send(new UserCreateMail($user, $data['password']));

            // Send mail to admin
            Mail::to(config('mail.admin_email'))
                ->send(new AdminNewUserNotificationMail($user));
        } catch (\Throwable $e) {
            Log::error('Failed to send user creation email: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e,
            ]);
        }
        return $user;
    }
}
