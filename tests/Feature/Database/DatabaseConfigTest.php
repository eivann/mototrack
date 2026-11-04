<?php

use Illuminate\Support\Facades\DB;

/**
 * RD2-R4: MySQL / MariaDB connections were removed; PostgreSQL (Supabase) and SQLite (tests/local) remain.
 * Read-only checks: nothing here connects to any server.
 */

test('the project config defines only PostgreSQL, SQLite (and the untouched SQL Server stub)', function () {
    // Laravel merges its own framework defaults into the runtime `database.connections`, so read the
    // project's own file to see what this application actually defines.
    $project = include base_path('config/database.php');
    $connections = array_keys($project['connections']);
    expect($connections)->toBe(['sqlite', 'pgsql', 'sqlsrv']);

    $pg = $project['connections']['pgsql'];
    expect($pg['driver'])->toBe('pgsql')->and($pg['search_path'])->toBe('public')->and($pg)->toHaveKey('sslmode');
});

test('the test suite is pinned to an isolated in-memory SQLite database', function () {
    expect(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(DB::connection()->getDriverName())->toBe('sqlite');
});

test('no application code or config references MySQL-only APIs', function () {
    $hits = [];
    foreach (['app', 'config', 'database', 'routes'] as $dir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($dir), FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            // Comments in older migrations may mention MySQL while explaining portability; code must not use it.
            $code = preg_replace('~/\*.*?\*/|//[^\n]*|#[^\n]*~s', '', file_get_contents($file->getPathname()));
            if (preg_match('/Pdo\\\\Mysql|PDO::MYSQL_|[\'"]mysql[\'"]|[\'"]mariadb[\'"]|ON DUPLICATE KEY|GROUP_CONCAT|DATE_FORMAT\(/i', $code)) {
                $hits[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }
    }
    expect($hits)->toBe([]);
});
