<?php

// Local recovery rehearsal only; never changes the configured source database.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

require __DIR__.'/../POS backend/vendor/autoload.php';
$app = require __DIR__.'/../POS backend/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$temporaryFiles = [];

function encryptArtifact(string $source, string $target, string $key): void
{
    [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
    $input = fopen($source, 'rb'); $output = fopen($target, 'xb');
    fwrite($output, $header);
    while (! feof($input)) {
        $plain = fread($input, 65536);
        $tag = feof($input) ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
        $cipher = sodium_crypto_secretstream_xchacha20poly1305_push($state, $plain, '', $tag);
        fwrite($output, pack('N', strlen($cipher)).$cipher);
    }
    fclose($input); fclose($output);
}

function decryptArtifact(string $source, string $target, string $key): void
{
    $input = fopen($source, 'rb'); $output = fopen($target, 'xb');
    $header = fread($input, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
    $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $key);
    $final = false;
    while (! feof($input)) {
        $length = fread($input, 4);
        if ($length === '') break;
        if (strlen($length) !== 4 || $final) throw new RuntimeException('Invalid encrypted artifact.');
        $size = unpack('Nlength', $length)['length'];
        if ($size > 65536 + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES) throw new RuntimeException('Invalid encrypted frame.');
        $result = sodium_crypto_secretstream_xchacha20poly1305_pull($state, fread($input, $size));
        if ($result === false) throw new RuntimeException('Artifact authentication failed.');
        [$plain, $tag] = $result;
        fwrite($output, $plain);
        $final = $tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
    }
    fclose($input); fclose($output);
    if (! $final) throw new RuntimeException('Truncated encrypted artifact.');
}

function capturedMatches(mixed $expected, mixed $actual): bool
{
    if (! is_array($expected) || array_is_list($expected)) return $expected === $actual;
    if (! is_array($actual)) return false;
    foreach ($expected as $key => $value) {
        if (! array_key_exists($key, $actual) || ! capturedMatches($value, $actual[$key])) return false;
    }
    return true;
}

try {
    [$script, $action, $backupId] = $argv + [null, null, null];
    if (! in_array($action, ['backup', 'restore'], true) || ! preg_match('/^backup_[a-f0-9]{32}$/D', $backupId ?? '')) throw new RuntimeException('Invalid action or backup ID.');
    $directory = __DIR__.'/../docs/audit/private/'.$backupId;
    if (! is_dir($directory)) throw new RuntimeException('Use the PowerShell wrapper to create the protected key first.');
    $key = base64_decode(getenv('LEGACY_BACKUP_KEY') ?: '', true);
    if (strlen($key ?: '') !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) throw new RuntimeException('A valid recovery key is required.');
    $connection = config('database.default');
    $config = DB::connection($connection)->getConfig();
    if (($config['driver'] ?? null) !== 'mysql' || ! in_array($config['host'], ['127.0.0.1', 'localhost', '::1'], true)) throw new RuntimeException('This rehearsal is restricted to a local MySQL/MariaDB source.');
    $mysqlDirectory = dirname(PHP_BINARY, 2).'/mysql/bin';
    $args = ['--host='.$config['host'], '--port='.($config['port'] ?: '3306'), '--user='.$config['username'], '--default-character-set=utf8mb4'];
    $environment = ['MYSQL_PWD' => $config['password'] ?? ''];
    if ($action === 'backup') {
        if (Artisan::call('legacy:audit', ['--output' => $directory.'/source.json']) !== 0) throw new RuntimeException('Source audit is incomplete.');
        $dumpFile = $directory.'/database.sql'; $temporaryFiles[] = $dumpFile;
        $dump = new Process(array_merge([$mysqlDirectory.'/mysqldump.exe'], $args, ['--single-transaction', '--quick', '--skip-add-locks', '--skip-add-drop-table', '--hex-blob', '--result-file='.$dumpFile, $config['database']]), null, $environment);
        $dump->setTimeout(300); $dump->run();
        if (! $dump->isSuccessful()) throw new RuntimeException('Database export failed; check local dump compatibility and grants.');
        encryptArtifact($dumpFile, $directory.'/database.sql.enc', $key);

        $archiveFile = $directory.'/application.zip'; $temporaryFiles[] = $archiveFile;
        $zip = new ZipArchive();
        if ($zip->open($archiveFile, ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new RuntimeException('Cannot create files backup.');
        $backend = realpath(__DIR__.'/../POS backend');
        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($backend, FilesystemIterator::SKIP_DOTS),
            function ($file) use ($backend) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($backend) + 1));
                return ! $file->isLink() && ! preg_match('#(^|/)(vendor|node_modules|\.git)(/|$)|^storage/(framework|logs)(/|$)|^bootstrap/cache(/|$)#', $relative);
            }
        );
        $files = new RecursiveIteratorIterator($filter);
        $manifest = [];
        foreach ($files as $file) {
            if (! $file->isFile() || $file->isLink()) continue;
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($backend) + 1));
            if (preg_match('#(^|/)(vendor|node_modules|\.git)/|^storage/(framework|logs)/|^bootstrap/cache/#', $relative)) continue;
            $zip->addFile($file->getPathname(), $relative);
            $manifest[$relative] = hash_file('sha256', $file->getPathname());
        }
        $zip->close();
        encryptArtifact($archiveFile, $directory.'/application.zip.enc', $key);
        file_put_contents($directory.'/files-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo 'Encrypted database and application/config backups created: '.$backupId.PHP_EOL;
    } else {
        $restoreFile = $directory.'/restore-'.bin2hex(random_bytes(6)).'.sql'; $temporaryFiles[] = $restoreFile;
        decryptArtifact($directory.'/database.sql.enc', $restoreFile, $key);
        $database = 'bu_restore_'.bin2hex(random_bytes(8));
        file_put_contents($directory.'/restore-target.json', json_encode(['staging_database' => $database], JSON_THROW_ON_ERROR));
        // Name is generated internally, cannot equal any user-selected source.
        DB::statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4');
        $restore = new Process(array_merge([$mysqlDirectory.'/mysql.exe'], $args, [$database]), null, $environment);
        $input = fopen($restoreFile, 'rb'); $restore->setInput($input); $restore->setTimeout(300); $restore->run(); fclose($input);
        if (! $restore->isSuccessful()) throw new RuntimeException('Isolated database import failed.');
        config(['database.connections.recovery' => array_merge($config, ['database' => $database, 'url' => null, 'name' => 'recovery']), 'database.default' => 'recovery']);
        DB::purge('recovery');
        if (Artisan::call('legacy:audit', ['--output' => $directory.'/restored.json']) !== 0) throw new RuntimeException('Restored database audit failed.');
        $source = json_decode(file_get_contents($directory.'/source.json'), true, 512, JSON_THROW_ON_ERROR);
        $restored = json_decode(file_get_contents($directory.'/restored.json'), true, 512, JSON_THROW_ON_ERROR);
        // A same-schema FK naturally reports the new database name after a
        // restore. Normalize only that self-reference; retain all other details.
        $normalize = static function (array $tables, string $schema): array {
            foreach ($tables as &$table) {
                foreach ($table['foreign_keys'] as &$foreignKey) {
                    if (($foreignKey['foreign_schema'] ?? null) === $schema) $foreignKey['foreign_schema'] = '{self}';
                }
                unset($foreignKey);
            }
            unset($table);
            return $tables;
        };
        // Compare every captured baseline field. Later audit versions may add
        // checks without invalidating backups captured before those additions.
        if (! capturedMatches($source['baseline'], $restored['baseline']) || ! capturedMatches($source['integrity'], $restored['integrity']) || $normalize($source['tables'], $config['database']) !== $normalize($restored['tables'], $database)) throw new RuntimeException('Database restore does not reconcile; preserve reports for review.');
        $restoredZip = $directory.'/verify-'.bin2hex(random_bytes(6)).'.zip'; $temporaryFiles[] = $restoredZip;
        decryptArtifact($directory.'/application.zip.enc', $restoredZip, $key);
        $zip = new ZipArchive(); $zip->open($restoredZip);
        $manifest = json_decode(file_get_contents($directory.'/files-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($manifest as $name => $hash) {
            $stream = $zip->getStream($name);
            if (! $stream) throw new RuntimeException('Missing file in restored archive.');
            $digest = hash_init('sha256'); hash_update_stream($digest, $stream); fclose($stream);
            if (! hash_equals($hash, hash_final($digest))) throw new RuntimeException('Restored file checksum mismatch.');
        }
        $zip->close();
        file_put_contents($directory.'/verification.json', json_encode(['verified_at' => date(DATE_ATOM), 'staging_database' => $database, 'database_reconciled' => true, 'files_verified' => count($manifest)], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo 'Restore verified: table DDL/counts, business fingerprints, stock balances, financial totals and '.count($manifest).' files. Isolated database retained: '.$database.PHP_EOL;
    }
} catch (Throwable $exception) {
    // Underlying connection/process exceptions can contain credentials or SQL.
    fwrite(STDERR, 'Recovery failed ('.$exception::class.'). '.($exception instanceof RuntimeException && ! $exception instanceof PDOException ? $exception->getMessage() : 'Inspect local configuration and grants without printing secrets.').PHP_EOL);
    $exitCode = 1;
} finally {
    foreach ($temporaryFiles as $file) if (is_file($file)) unlink($file);
    if (isset($key) && is_string($key)) sodium_memzero($key);
}
exit($exitCode ?? 0);
