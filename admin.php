<?php
require 'config.php'; require 'crypto.php';
if (empty($_SESSION['admin'])) { header('Location: login.php'); exit; }
$cfg = $pdo->query("SELECT * FROM kunci_config WHERE id=1")->fetch();
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['hapus'])) {
        $pdo->prepare("DELETE FROM data_terenkripsi WHERE id=?")->execute([(int)$_POST['hapus']]);
        $msg = 'Data dihapus.';
    } elseif (trim($_POST['judul'] ?? '') !== '') {
        [$j, $i, $k] = encryptRecord($_POST['judul'], $_POST['isi'] ?? '', $_SESSION['sym_key'], $cfg['rsa_public_key']);
        $pdo->prepare("INSERT INTO data_terenkripsi (judul_enc, isi_enc, key_enc) VALUES (?,?,?)")->execute([$j, $i, $k]);
        $msg = 'Data dienkripsi dan disimpan.';
    }
}
$rows = $pdo->query("SELECT * FROM data_terenkripsi ORDER BY id DESC")->fetchAll();
$e = fn($s) => htmlspecialchars((string)$s);
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin</title><link rel="stylesheet" href="style.css"></head><body>
<header class="bar"><strong>Brankas Data</strong><a href="logout.php">Keluar</a></header>
<main class="wide">
<?php if ($msg): ?><p class="ok"><?= $e($msg) ?></p><?php endif; ?>

<section class="card">
  <h2>Simpan data baru</h2>
  <form method="post">
    <label for="judul">Judul</label><input id="judul" name="judul" required>
    <label for="isi">Isi</label><textarea id="isi" name="isi" rows="4"></textarea>
    <button class="btn">Enkripsi dan simpan</button>
  </form>
</section>

<section class="card">
  <h2>Data tersimpan (<?= count($rows) ?>)</h2>
  <?php if (!$rows): ?><p>Belum ada data. Isi form di atas untuk menyimpan data pertama.</p><?php endif; ?>
  <div class="scroll"><table>
    <?php if ($rows): ?><tr><th>ID</th><th>Ciphertext (di database)</th><th>Hasil dekripsi</th><th></th></tr><?php endif; ?>
    <?php foreach ($rows as $r):
        $d = decryptRecord($r, $_SESSION['sym_key'], $_SESSION['rsa_priv']); ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td class="cipher">
        judul: <?= $e(substr($r['judul_enc'], 0, 40)) ?>…<br>
        isi: <?= $e(substr($r['isi_enc'], 0, 40)) ?>…<br>
        kunci: <?= $e(substr($r['key_enc'], 0, 40)) ?>…
      </td>
      <td><?php if ($d): ?><strong><?= $e($d[0]) ?></strong><br><?= nl2br($e($d[1])) ?>
          <?php else: ?><span class="error">Gagal didekripsi</span><?php endif; ?></td>
      <td><form method="post"><button class="btn ghost" name="hapus" value="<?= (int)$r['id'] ?>">Hapus</button></form></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</section>
</main></body></html>
