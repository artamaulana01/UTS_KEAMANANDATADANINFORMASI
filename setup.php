<?php
// Jalankan SEKALI untuk membuat pasangan kunci. Setelah itu halaman ini terkunci.
require 'config.php'; require 'crypto.php';
$sudah = $pdo->query("SELECT COUNT(*) FROM kunci_config")->fetchColumn() > 0;
$priv = $sym = null; $err = null;

if (!$sudah && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cari openssl.cnf (dibutuhkan PHP di Windows untuk membuat kunci)
    $kandidat = array_merge(
        [getenv('OPENSSL_CONF') ?: ''],
        glob('C:/laragon/bin/php/*/extras/ssl/openssl.cnf') ?: [],
        glob('C:/laragon/bin/apache/*/conf/openssl.cnf') ?: [],
        glob('C:/laragon/bin/nginx/*/conf/openssl.cnf') ?: [],
        glob('C:/laragon/etc/ssl/openssl.cnf') ?: []
    );
    $cnf = null;
    foreach ($kandidat as $k) {
        if ($k && is_file($k)) { $cnf = $k; break; }
    }

    $opts = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
    if ($cnf) $opts['config'] = $cnf;

    $res = openssl_pkey_new($opts);
    if (!$res) {
        $err = 'Gagal membuat kunci RSA: ' . openssl_error_string()
             . ($cnf ? '' : ' (openssl.cnf tidak ditemukan)');
    } else {
        openssl_pkey_export($res, $priv, null, $cnf ? ['config' => $cnf] : []);
        $pub = openssl_pkey_get_details($res)['key'];
        $sym = bin2hex(random_bytes(12));
        $pdo->prepare("INSERT INTO kunci_config (id, sym_key_hash, rsa_public_key) VALUES (1,?,?)")
            ->execute([password_hash($sym, PASSWORD_DEFAULT), $pub]);
        $sudah = true;
    }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Setup kunci</title><link rel="stylesheet" href="style.css"></head><body>
<main class="card">
<h1>Setup kunci admin</h1>
<?php if ($err): ?><p class="error"><?= htmlspecialchars($err) ?></p><?php endif; ?>
<?php if ($priv): ?>
  <p class="warn">Simpan kedua kunci ini sekarang. Kunci tidak ditampilkan lagi dan tidak bisa dipulihkan.</p>
  <label>Kunci simetris</label>
  <textarea readonly rows="1"><?= htmlspecialchars($sym) ?></textarea>
  <label>Kunci privat RSA</label>
  <textarea readonly rows="10"><?= htmlspecialchars($priv) ?></textarea>
  <a class="btn" download="private_key.pem" href="data:application/x-pem-file;base64,<?= base64_encode($priv) ?>">Unduh private_key.pem</a>
  <a class="btn ghost" href="login.php">Lanjut ke login</a>
<?php elseif ($sudah): ?>
  <p>Kunci sudah dibuat. Setup tidak bisa dijalankan ulang.</p>
  <a class="btn" href="login.php">Ke halaman login</a>
<?php else: ?>
  <p>Membuat kunci simetris acak dan pasangan kunci RSA 2048-bit. Hash kunci simetris dan kunci publik disimpan di database; kunci privat tidak disimpan.</p>
  <form method="post"><button class="btn">Buat kunci</button></form>
<?php endif; ?>
</main></body></html>