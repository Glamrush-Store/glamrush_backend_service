<?php

namespace App\Listeners\Auth;

use App\Domain\User\Events\UserRegistered;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Mail\Auth\WelcomeCustomerMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

final class SendWelcomeEmail implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        $user = User::query()->whereKey($event->userId)->first();

        if ($user === null || ! $user->email) {
            return;
        }

        Mail::to($user->email)->send(new WelcomeCustomerMail($user));
    }
}
