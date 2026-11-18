<?php

if (!defined('WHMCS')) {
    exit('Acesso restrito');
}

function thxPromoCacheDir()
{
    $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/thx-promocoes-' . hash('sha256', __DIR__);
    if (!is_dir($dir)) {
        @mkdir($dir, 0700);
    }
    clearstatcache(true, $dir);
    if (!is_dir($dir) || is_link($dir) || (fileperms($dir) & 0077) !== 0) {
        return null;
    }
    if (function_exists('posix_geteuid') && fileowner($dir) !== posix_geteuid()) {
        return null;
    }
    $real = realpath($dir);
    foreach ([realpath(__DIR__ . '/../..'), realpath($_SERVER['DOCUMENT_ROOT'] ?? '')] as $root) {
        if ($root && ($real === $root || strpos($real, rtrim($root, '/') . '/') === 0)) {
            return null;
        }
    }
    return $real ?: null;
}

function thxPromoCacheAtomic($file, $content)
{
    $tmp = @tempnam(dirname($file), '.tmp-');
    if (!$tmp) return false;
    try {
        return dirname($tmp) === dirname($file)
            && @chmod($tmp, 0600)
            && @file_put_contents($tmp, $content) === strlen($content)
            && @rename($tmp, $file);
    } finally {
        if (is_file($tmp)) @unlink($tmp);
    }
}

function thxPromoCacheLocked(callable $operation)
{
    $lock = null;
    try {
        $dir = thxPromoCacheDir();
        if (!$dir) return null;
        $lock = @fopen($dir . '/cache.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) return null;
        return $operation($dir);
    } catch (\Throwable $e) {
        return null;
    } finally {
        if (is_resource($lock)) fclose($lock);
    }
}

function thxPromoCacheRead($slug, $version)
{
    return thxPromoCacheLocked(function ($dir) use ($slug, $version) {
        $generationFile = $dir . '/generation';
        $generation = is_file($generationFile) ? @file_get_contents($generationFile) : false;
        if (!is_string($generation) || !preg_match('/^[a-f0-9]{32}$/D', $generation)) {
            $generation = bin2hex(random_bytes(16));
            if (!thxPromoCacheAtomic($generationFile, $generation)) return null;
        }
        $state = ['generation' => $generation, 'html' => null, 'id' => 0];
        $file = $dir . '/' . hash('sha256', $slug) . '.html';
        clearstatcache(true, $file);
        $raw = @file_get_contents($file);
        if (!is_string($raw)) return $state;
        $parts = explode("\n", $raw, 2);
        $prefix = '<!-- THX-CACHE ' . $generation . ' ' . $version . ' ';
        if (count($parts) === 2 && $parts[1] !== '' && strpos($parts[0], $prefix) === 0
            && preg_match('/^([0-9]+) -->$/D', substr($parts[0], strlen($prefix)), $match)) {
            $state['id'] = (int) $match[1];
            $state['html'] = $parts[1];
        }
        return $state;
    });
}

function thxPromoCacheWrite($slug, $version, $generation, $id, $html)
{
    return thxPromoCacheLocked(function ($dir) use ($slug, $version, $generation, $id, $html) {
        if (@file_get_contents($dir . '/generation') !== $generation) return false;
        $file = $dir . '/' . hash('sha256', $slug) . '.html';
        return thxPromoCacheAtomic($file, '<!-- THX-CACHE ' . $generation . ' ' . $version . ' ' . (int) $id . " -->\n" . $html);
    }) === true;
}

function thxPromoCacheClear()
{
    return thxPromoCacheLocked(function ($dir) {
        if (!thxPromoCacheAtomic($dir . '/generation', bin2hex(random_bytes(16)))) return false;
        foreach (glob($dir . '/*.html') ?: [] as $file) @unlink($file);
        return true;
    }) === true;
}

function thxPromoCacheStats()
{
    return thxPromoCacheLocked(function ($dir) {
        $files = glob($dir . '/*.html') ?: [];
        $bytes = 0;
        foreach ($files as $file) $bytes += (int) @filesize($file);
        return ['files' => count($files), 'bytes' => $bytes];
    });
}

function thxPromoDeliver($html, $id, $token, $clienteIdentificado, $status)
{
    if (!headers_sent()) {
        header('Cache-Control: private, no-store, max-age=0');
        header('X-THX-Promocao-Cache: ' . $status);
    }
    if ($id && $_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['adminid'])) {
        try {
            \WHMCS\Database\Capsule::table('mod_promocoes_thx')->where('id', $id)->increment('acessos');
        } catch (\Throwable $e) {
            error_log('Promoções THX: falha ao contabilizar acesso.');
        }
    }
    $start = '<!-- THX_PROMO_ANONYMOUS_START -->';
    $end = '<!-- THX_PROMO_ANONYMOUS_END -->';
    if ($clienteIdentificado) {
        $first = strpos($html, $start);
        $last = strpos($html, $end);
        if ($first !== false && $last !== false && $last > $first) {
            $html = substr_replace($html, '', $first, $last + strlen($end) - $first);
        }
    }
    echo str_replace([$start, $end, '__THX_PROMO_TOKEN_SUFFIX__'], ['', '', $token !== '' ? '/' . rawurlencode($token) : ''], $html);
}
