<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('exports the site to static files', function (): void {
    $root = sys_get_temp_dir().'/site-export-'.bin2hex(random_bytes(4));

    config([
        'filesystems.disks.export-test' => ['driver' => 'local', 'root' => $root],
        'export.disk' => 'export-test',
    ]);

    $this->artisan('export', ['--skip-all' => true])->assertSuccessful();

    expect(File::exists("{$root}/index.html"))->toBeTrue()
        ->and(File::exists("{$root}/docs/index.html"))->toBeTrue()
        ->and(File::exists("{$root}/docs/getting-started/menus/index.html"))->toBeTrue()
        ->and(File::exists("{$root}/404.html"))->toBeTrue()
        ->and(File::exists("{$root}/sitemap.xml"))->toBeTrue()
        ->and(trim(File::get("{$root}/CNAME")))->toBe('currentphp.com')
        ->and(File::exists("{$root}/index.php"))->toBeFalse();

    File::deleteDirectory($root);
});
