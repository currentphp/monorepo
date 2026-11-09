<?php

use App\Logging\ConfigureLogging;
use App\Models\User;
use App\Notifications\Auth\VerifyEmail;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('requests receive fresh server generated IDs and scoped safe logging context', function () {
    Route::get('/context-check', function () {
        expect(Context::all())->toHaveKeys(['request_id']);
        Log::info('Context check');

        return response()->noContent();
    })->name('context-check');
    $first = $this->withHeader('X-Request-ID', str_repeat('untrusted', 100))->get('/context-check');
    $id = $first->headers->get('X-Request-ID');
    expect(Str::isUuid($id))->toBeTrue();
    expect(Context::all())->toBe([]);
    $second = $this->get('/context-check');
    expect($second->headers->get('X-Request-ID'))->not->toBe($id);
    expect(Context::all())->toBe([]);
});

test('structured logs redact sensitive fields URLs and exception contents', function () {
    $path = tempnam(sys_get_temp_dir(), 'foundation-log-');
    try {
        config(['logging.channels.operations-test' => [
            'driver' => 'single',
            'path' => $path,
            'tap' => [ConfigureLogging::class],
        ]]);
        $logger = Log::channel('operations-test');
        Context::add('request_id', 'safe-request-id');
        $logger->info('Visit https://example.test/reset-password/secret-token?email=private', [
            'password' => 'secret-password', 'nested' => ['token' => 'secret-token'],
            'mail_body' => 'private-mail',
        ]);
        $logger->error('secret exception message', ['exception' => new RuntimeException('secret exception message')]);
        $contents = file_get_contents($path);
        expect($contents)->not->toContain('secret-token', 'secret-password', 'private-mail', 'secret exception message');
        expect($contents)->toContain('RuntimeException', 'safe-request-id');
        foreach (array_filter(explode("\n", $contents)) as $line) {
            expect(json_decode($line, true, flags: JSON_THROW_ON_ERROR))->toHaveKeys(['message', 'context']);
        }
    } finally {
        Log::forgetChannel('operations-test');
        Context::flush();
        unlink($path);
    }
});

test('production errors return safe Inertia and JSON responses with request IDs', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/failure-check', fn () => throw new RuntimeException('secret-password token=secret-token'));
    $this->get('/failure-check')->assertStatus(500)->assertDontSee('secret-password')->assertDontSee('secret-token')
        ->assertInertia(fn (Assert $page) => $page->component('error')->where('status', 500)->has('requestId'));
    $response = $this->getJson('/failure-check')->assertStatus(500)->assertJsonPath('message', 'Internal Server Error')->assertDontSee('secret-password');
    expect($response->json('request_id'))->toBe($response->headers->get('X-Request-ID'));
    expect(Context::all())->toBe([]);
});

test('credential bearing notifications cannot use a log transport even through failover', function (string $mailer) {
    config(['mail.default' => $mailer, 'mail.mailers.failover.mailers' => ['log']]);
    expect(fn () => Mail::raw('secret-token', fn ($message) => $message->to('test@example.test')->subject('Private')))
        ->toThrow(LogicException::class, 'Mail logging is disabled');
})->with(['log', 'failover']);

test('verification queue propagates only correlation metadata and clears it after a job', function () {
    config(['queue.default' => 'database']);
    $user = User::factory()->unverified()->create();
    $id = (string) Str::uuid();
    Context::add(['request_id' => $id, 'password' => 'never-propagate']);
    Context::addHidden('token', 'never-propagate');
    DB::transaction(fn () => $user->notify(new VerifyEmail));
    $payload = DB::table('jobs')->sole()->payload;
    expect($payload)->toContain($id)->not->toContain('never-propagate');
    $this->artisan('queue:work', ['--once' => true, '--quiet' => true])->assertSuccessful();
    $this->assertDatabaseCount('jobs', 0);
    expect(Context::all())->toBe([]);
    expect(Context::allHidden())->toBe([]);
});

test('the default local disk does not expose private files without a signed URL', function () {
    expect(config('filesystems.default'))->toBe('local');
    expect(config('filesystems.disks.local.root'))->toBe(storage_path('app/private'));
    Storage::fake('local');
    Storage::disk('local')->put('private-check.txt', 'private-content');
    $this->get('/storage/private-check.txt')->assertForbidden()->assertDontSee('private-content');
});

test('liveness and unknown pages have request IDs without exposing debug information', function () {
    config(['app.debug' => false]);
    $this->get('/up')->assertOk()->assertHeader('X-Request-ID');
    $this->get('/does-not-exist')->assertNotFound()->assertHeader('X-Request-ID')->assertDontSee('Stack trace');
});

test('failed verification jobs retain correlation in exception logs without leaking it to the next job', function () {
    $path = tempnam(sys_get_temp_dir(), 'foundation-queue-log-');
    config([
        'queue.default' => 'database',
        'mail.default' => 'log',
        'logging.default' => 'queue-test',
        'logging.channels.queue-test' => ['driver' => 'single', 'path' => $path, 'tap' => [ConfigureLogging::class]],
    ]);
    $user = User::factory()->unverified()->create();
    $id = (string) Str::uuid();
    Context::add('request_id', $id);
    DB::transaction(fn () => $user->notify(new VerifyEmail));
    Context::flush();
    DB::transaction(fn () => $user->notify(new VerifyEmail));

    $contexts = [];
    Queue::before(function () use (&$contexts): void {
        $contexts[] = Context::all();
    });
    Queue::exceptionOccurred(function (): void {
        config(['mail.default' => 'array']);
        Mail::forgetMailers();
    });

    try {
        $this->artisan('queue:work', ['--stop-when-empty' => true, '--sleep' => 0, '--quiet' => true])->assertSuccessful();
        $records = array_map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR), array_filter(explode("\n", file_get_contents($path))));
        $exception = collect($records)->firstWhere('message', 'Application exception');
        expect($exception)->not->toBeNull();
        expect($exception['extra']['request_id'] ?? null)->toBe($id);
        expect($contexts)->toBe([['request_id' => $id], []]);
        expect(Context::all())->toBe([]);
        $this->assertDatabaseCount('jobs', 1);
    } finally {
        Context::flush();
        Log::forgetChannel('queue-test');
        unlink($path);
    }
});

test('safe production errors preserve retry headers for browser and JSON clients', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/throttle-check', fn () => abort(429, 'private-details', ['Retry-After' => 60]));
    $this->get('/throttle-check')->assertTooManyRequests()->assertHeader('Retry-After', '60')->assertDontSee('private-details');
    $this->getJson('/throttle-check')->assertTooManyRequests()->assertHeader('Retry-After', '60')->assertJsonPath('message', 'Too Many Requests');
});
