<?php
// config/env.php — Loader .env TANPA composer (cukup PHP bawaan).
// Membaca file .env di root project dan mengisi getenv()/$_ENV.
// Prioritas: environment sistem (hosting) MENANG atas .env, jadi file .env
// lokal tidak akan menimpa setting production.

function load_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        // Lepas kutip pembungkus: "isi" atau 'isi'.
        $len = strlen($val);
        if ($len >= 2 && (($val[0] === '"' && $val[$len - 1] === '"') || ($val[0] === "'" && $val[$len - 1] === "'"))) {
            $val = substr($val, 1, -1);
        }
        if ($key === '' || getenv($key) !== false) continue; // env sistem menang
        putenv($key . '=' . $val);
        $_ENV[$key] = $val;
    }
}
