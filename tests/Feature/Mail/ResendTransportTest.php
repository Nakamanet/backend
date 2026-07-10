<?php

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

test('the resend mailer is defined with the resend transport', function () {
    expect(config('mail.mailers.resend.transport'))->toBe('resend');
});

test('the resend mailer transport is registered and resolvable', function () {
    // Fake the mail manager so no real network I/O happens, then resolve the
    // configured mailer to prove Laravel can build the "resend" transport
    // (i.e. resend/resend-php is installed and wired via config/mail.php).
    Mail::fake();

    $mailer = Mail::mailer('resend');

    expect($mailer)->not->toBeNull();
});

test('a mailable dispatched through the default mailer is sent via the resend pipeline', function () {
    Mail::fake();

    $mailable = new class extends Mailable
    {
        public function build(): self
        {
            return $this->subject('Resend smoke test')
                ->html('<p>Hello from the Resend transport smoke test.</p>');
        }
    };

    Mail::to('smoke-test@example.com')->send($mailable);

    Mail::assertSent($mailable::class, function ($sent) {
        return $sent->hasTo('smoke-test@example.com');
    });
});
