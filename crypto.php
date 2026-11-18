<?php
// Enkripsi hibrida:
// 1. Tiap data punya kunci acak (DEK) -> isi dienkripsi AES-256-CBC dengan DEK
// 2. DEK dibungkus AES-256-CBC memakai KUNCI SIMETRIS admin
// 3. Hasil bungkusan dibungkus lagi dengan RSA-OAEP memakai KUNCI PUBLIK
// Untuk membuka data dibutuhkan kunci simetris DAN kunci privat RSA.

const CIPHER = 'aes-256-cbc';

function deriveKey(string $secret): string {
    return hash('sha256', $secret, true);
}

function aesEnc(string $plain, string $rawKey): string {
    $iv = random_bytes(16);
    $ct = openssl_encrypt($plain, CIPHER, $rawKey, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $ct);
}

function aesDec(string $b64, string $rawKey) {
    $raw = base64_decode($b64, true);
    if ($raw === false || strlen($raw) < 17) return false;
    return openssl_decrypt(substr($raw, 16), CIPHER, $rawKey, OPENSSL_RAW_DATA, substr($raw, 0, 16));
}

function encryptRecord(string $judul, string $isi, string $symKey, string $pubPem): array {
    $dek = random_bytes(32);
    $wrapped = aesEnc($dek, deriveKey($symKey));
    if (!openssl_public_encrypt($wrapped, $rsaOut, $pubPem, OPENSSL_PKCS1_OAEP_PADDING)) {
        throw new RuntimeException('Enkripsi RSA gagal');
    }
    return [aesEnc($judul, $dek), aesEnc($isi, $dek), base64_encode($rsaOut)];
}

function decryptRecord(array $row, string $symKey, string $privPem) {
    $priv = openssl_pkey_get_private($privPem);
    if (!$priv) return false;
    if (!openssl_private_decrypt(base64_decode($row['key_enc']), $wrapped, $priv, OPENSSL_PKCS1_OAEP_PADDING)) return false;
    $dek = aesDec($wrapped, deriveKey($symKey));
    if ($dek === false) return false;
    $judul = aesDec($row['judul_enc'], $dek);
    $isi   = aesDec($row['isi_enc'], $dek);
    return ($judul === false || $isi === false) ? false : [$judul, $isi];
}

// Uji kepemilikan kunci privat: challenge acak dienkripsi dengan kunci publik,
// lalu harus bisa didekripsi dengan kunci privat yang diinput.
function verifyRsaPrivateKey(string $privPem, string $pubPem): bool {
    $priv = openssl_pkey_get_private($privPem);
    if (!$priv) return false;
    $challenge = bin2hex(random_bytes(16));
    if (!openssl_public_encrypt($challenge, $enc, $pubPem, OPENSSL_PKCS1_OAEP_PADDING)) return false;
    if (!openssl_private_decrypt($enc, $dec, $priv, OPENSSL_PKCS1_OAEP_PADDING)) return false;
    return hash_equals($challenge, $dec);
}
