<?php
require 'config.php'; require 'crypto.php';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sym  = trim($_POST['sym_key'] ?? '');
    $priv = trim($_POST['rsa_private'] ?? '');
    if (!empty($_FILES['rsa_file']['tmp_name']) && is_uploaded_file($_FILES['rsa_file']['tmp_name'])) {
        $priv = trim(file_get_contents($_FILES['rsa_file']['tmp_name']));
    }
    $cfg = $pdo->query("SELECT * FROM kunci_config WHERE id=1")->fetch();
    if (!$cfg) {
        $error = 'Kunci belum dibuat. Jalankan setup.php terlebih dahulu.';
    } elseif (!password_verify($sym, $cfg['sym_key_hash'])) {
        $error = 'Kunci simetris salah.';
    } elseif (!verifyRsaPrivateKey($priv, $cfg['rsa_public_key'])) {
        $error = 'Kunci privat RSA tidak cocok dengan kunci publik di sistem.';
    } else {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['sym_key'] = $sym;
        $_SESSION['rsa_priv'] = $priv;
        header('Location: admin.php'); exit;
    }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk admin</title><link rel="stylesheet" href="style.css"></head><body>
<main class="card">
<h1>Masuk admin</h1>
<p>Masukkan kedua kunci. Tidak ada username atau password.</p>
<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" autocomplete="off">
  <label for="sym_key">Kunci simetris</label>
  <input id="sym_key" name="sym_key" type="password" required>
  <label for="rsa_private">Kunci privat RSA (tempel isi PEM)</label>
  <textarea id="rsa_private" name="rsa_private" rows="8" placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
  <label for="rsa_file">atau unggah file .pem</label>
  <input id="rsa_file" name="rsa_file" type="file" accept=".pem,.key,.txt">
  <button class="btn">Buka brankas</button>
</form>
<p class="small"><a href="index.php">Kembali ke beranda</a></p>
</main></body></html>
