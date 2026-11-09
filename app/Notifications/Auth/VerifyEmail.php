<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail as FrameworkVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Routing\UrlGenerator;

class VerifyEmail extends FrameworkVerifyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct()
    {
        $this->afterCommit();
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Generate the signature against the configured origin, never a request host.
     *
     * @param  User  $notifiable
     */
    protected function verificationUrl($notifiable): string
    {
        $origin = config('app.url');
        $scheme = parse_url($origin, PHP_URL_SCHEME);
        $generator = clone app(UrlGenerator::class);
        $generator->useOrigin($origin);
        $generator->forceScheme(is_string($scheme) ? $scheme : null);

        return $generator->temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);
    }

    /** @param  User  $notifiable */
    public function shouldSend($notifiable, string $channel): bool
    {
        return ! $notifiable->hasVerifiedEmail();
    }
}
