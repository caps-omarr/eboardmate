<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 🚀 Force all assets and links to use HTTPS for Cloudflare Tunnel
        if (request()->header('x-forwarded-proto') === 'https' || !app()->environment('local')) {
            URL::forceScheme('https');
        }

        // 🚀 Tell Laravel how to connect to Brevo's API
        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory())->create(
                Dsn::fromString("brevo+api://" . env('BREVO_API_KEY') . "@default")
            );
        });
    }
}