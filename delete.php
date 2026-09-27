<?php
/**
 * Delete Product Handler (POST Method + Proteksi CSRF + Redirect)
 * File: delete.php
 * Praktikum 3 - Pemrograman Web
 * 
 * PENTING (Slide 17 & 19):
 * Penghapusan data TIDAK BOLEH menggunakan link GET (misal: delete.php?id=1)
 * karena sangat rentan terhadap serangan CSRF.
 * Wajib menggunakan request POST dan validasi Token CSRF.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

// 1. Pastikan request menggunakan method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("<h3>405 Method Not Allowed</h3><p>Penghapusan produk hanya dapat dilakukan melalui metode HTTP POST demi keamanan data.</p>");
}

// 2. Verifikasi Token CSRF (Proteksi Keamanan 25%)
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    http_response_code(403);
    die("<h3>403 Forbidden</h3><p>Akses ditolak: Token CSRF tidak valid atau sesi Anda telah berakhir.</p>");
}

// 3. Validasi ID produk
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'ID produk yang ingin dihapus tidak valid.');
    header("Location: index.php");
    exit;
}

try {
    // Cari data produk terlebih dahulu untuk mengambil nama dan file gambarnya
    $stmt = $pdo->prepare("SELECT name, image FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash('danger', 'Produk tidak ditemukan atau sudah dihapus sebelumnya.');
        header("Location: index.php");
        exit;
    }

    // Eksekusi DELETE dengan PDO Prepared Statement
    $delete_stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $delete_stmt->execute([':id' => $id]);

    // Hapus berkas gambar fisik dari folder uploads jika ada
    if (!empty($product['image'])) {
        $image_path = __DIR__ . '/uploads/' . $product['image'];
        if (file_exists($image_path)) {
            @unlink($image_path);
        }
    }

    // Set flash message sukses dan redirect ke halaman index
    set_flash('success', "Produk \"{$product['name']}\" berhasil dihapus dari inventaris.");
    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    set_flash('danger', "Gagal menghapus produk: " . $e->getMessage());
    header("Location: index.php");
    exit;
}
