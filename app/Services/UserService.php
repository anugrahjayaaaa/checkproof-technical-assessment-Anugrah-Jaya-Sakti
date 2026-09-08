<?php

namespace App\Services;

use App\Mail\AdminNewUserNotificationMail;
use App\Mail\UserCreateMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class UserService
{
    public function create(array $data): User
    {
        $user = User::create($data);

        //send email to user
        Mail::to($user->email)
            ->send(new UserCreateMail($user, $data['password']));
        
        //send mail to admin
        Mail::to(config('mail.admin_email'))
            ->send(new AdminNewUserNotificationMail($user));

        return $user;
    }
}
