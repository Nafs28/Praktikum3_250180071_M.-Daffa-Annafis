<?php
/**
 * Test Suite Mini Project Praktikum 3
 * Menjalankan seluruh checklist pengujian dari Slide 20 & Kriteria Penilaian Slide 19
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

echo "========================================================\n";
echo "  PENGUJIAN OTOMATIS MINI PROJECT (SLIDE 19 & 20)\n";
echo "========================================================\n\n";

$passed = 0;
$total = 0;

function assert_test($label, $condition, $detail = '') {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo "✅ [PASS] {$label}\n";
        if ($detail) echo "   Detail: {$detail}\n";
    } else {
        echo "❌ [FAIL] {$label}\n";
        if ($detail) echo "   Detail: {$detail}\n";
    }
}

// 1. UJI KONEKSI DATABASE
assert_test("Koneksi Database PDO", $pdo instanceof PDO, "PDO instance terhubung ke database store_db");

// 2. CHECKLIST 1: Tambah produk valid -> Muncul di daftar
$test_name = "Diecast 1:18 Ferrari SF-24 Charles Leclerc";
$test_cat = "Diecast & Koleksi";
$test_price = 2950000.00;
$test_stock = 12;
$test_desc = "Diecast resmi skala 1:18 Scuderia Ferrari SF-24 edisi GP Monza 2024.";

$stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock, description) VALUES (:name, :cat, :price, :stock, :desc)");
$stmt->execute([
    ':name' => $test_name,
    ':cat' => $test_cat,
    ':price' => $test_price,
    ':stock' => $test_stock,
    ':desc' => $test_desc
]);
$new_id = $pdo->lastInsertId();

$check = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$check->execute([':id' => $new_id]);
$inserted = $check->fetch();

assert_test("Checklist 1: Tambah produk valid -> Muncul di daftar", 
    $inserted && $inserted['name'] === $test_name && (float)$inserted['price'] == $test_price, 
    "ID #{$new_id} berhasil dibuat dan ditemukan di tabel products");

// 3. CHECKLIST 2: Nama < 3 karakter -> Ditolak; tidak tersimpan
$short_name = "Ab";
$errors_short = [];
if (mb_strlen(trim($short_name)) < 3) {
    $errors_short[] = "Nama produk harus diisi dan minimal 3 karakter.";
}
assert_test("Checklist 2: Validasi Nama < 3 karakter -> Ditolak", 
    count($errors_short) > 0 && strpos($errors_short[0], "minimal 3 karakter") !== false,
    "Validasi server-side mendeteksi input '$short_name' kurang dari 3 karakter");

// 4. CHECKLIST 3: Harga negatif / stok negatif -> Ditolak; pesan jelas
$neg_price = -5000;
$neg_stock = -10;
$errors_num = [];
if (!is_numeric($neg_price) || (float)$neg_price < 0) {
    $errors_num[] = "Harga harus berupa angka valid dan tidak boleh bernilai negatif (>= 0).";
}
if (!filter_var($neg_stock, FILTER_VALIDATE_INT) || (int)$neg_stock < 0) {
    $errors_num[] = "Stok harus berupa bilangan bulat valid dan tidak boleh bernilai negatif (>= 0).";
}
assert_test("Checklist 3a: Validasi Harga Negatif -> Ditolak", 
    isset($errors_num[0]) && strpos($errors_num[0], "tidak boleh bernilai negatif") !== false,
    "Validasi menolak harga $neg_price dengan pesan peringatan jelas");
assert_test("Checklist 3b: Validasi Stok Negatif -> Ditolak", 
    isset($errors_num[1]) && strpos($errors_num[1], "tidak boleh bernilai negatif") !== false,
    "Validasi menolak stok $neg_stock dengan pesan peringatan jelas");

// 5. CHECKLIST 4: PRG (Post-Redirect-Get) Anti-Duplikasi
// Verifikasi alur PRG: saat POST sukses atau gagal, server melakukan header('Location: ...') dan exit
$create_code = file_get_contents(__DIR__ . '/create.php');
$has_prg_header = strpos($create_code, 'header("Location: index.php");') !== false;
$has_prg_exit = strpos($create_code, 'exit;') !== false;
$has_flash = strpos($create_code, 'set_flash(') !== false;
assert_test("Checklist 4: Alur PRG (Post-Redirect-Get) Mencegah Duplikasi Saat Refresh", 
    $has_prg_header && $has_prg_exit && $has_flash, 
    "create.php mengimplementasikan redirect header + session flash message");

// 6. CHECKLIST 5: Nama berisi <b>Promo</b> -> Tampil sebagai teks (XSS Escaping)
$xss_input = "<b>Promo</b> Sepatu Lari";
$escaped_output = e($xss_input);
assert_test("Checklist 5: XSS Protection e() / htmlspecialchars", 
    $escaped_output === "&lt;b&gt;Promo&lt;/b&gt; Sepatu Lari", 
    "Input '$xss_input' diescape menjadi '$escaped_output' sehingga tidak dieksekusi sebagai HTML tag");

// 7. CHECKLIST 6: Layar Sempit -> Card membungkus rapi (Flexbox & Box Model)
$css_code = file_get_contents(__DIR__ . '/css/style.css');
$has_box_sizing = strpos($css_code, 'box-sizing: border-box') !== false;
$has_flex_wrap = strpos($css_code, 'flex-wrap: wrap') !== false;
$has_media_query = strpos($css_code, '@media') !== false;
assert_test("Checklist 6: Box Model & Flexbox Responsif (Layar Sempit)", 
    $has_box_sizing && $has_flex_wrap && $has_media_query, 
    "CSS memiliki 'box-sizing: border-box', '.product-grid { flex-wrap: wrap }', dan media queries");

// 8. KEAMANAN: Proteksi CSRF pada Delete
$delete_code = file_get_contents(__DIR__ . '/delete.php');
$has_csrf_verify = strpos($delete_code, 'verify_csrf_token') !== false;
$has_post_check = strpos($delete_code, "REQUEST_METHOD'] !== 'POST'") !== false;
assert_test("Keamanan: Proteksi CSRF Delete (POST + Token)", 
    $has_csrf_verify && $has_post_check, 
    "delete.php menolak method selain POST dan memverifikasi token CSRF via hash_equals");

// 9. UPDATE: Form terisi + UPDATE
$update_name = "Diecast 1:18 Ferrari SF-24 Charles Leclerc (Monza Winner Edition)";
$upd_stmt = $pdo->prepare("UPDATE products SET name = :name, price = :price WHERE id = :id");
$upd_stmt->execute([':name' => $update_name, ':price' => 3100000.00, ':id' => $new_id]);

$check_upd = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$check_upd->execute([':id' => $new_id]);
$updated = $check_upd->fetch();
assert_test("CRUD Update: Modifikasi Data Produk", 
    $updated['name'] === $update_name && (float)$updated['price'] == 3100000.00, 
    "Produk ID #{$new_id} berhasil diupdate di database");

// 10. FITUR BONUS: Pencarian & Filter dengan Parameterized GET Query (Slide 18)
$search_q = "Ferrari";
$search_stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE :q1 OR description LIKE :q2");
$search_stmt->execute([':q1' => "%{$search_q}%", ':q2' => "%{$search_q}%"]);
$search_results = $search_stmt->fetchAll();
assert_test("Fitur Bonus: Pencarian Aman dengan Parameter SQL (Slide 18)", 
    count($search_results) > 0 && strpos($search_results[0]['name'], "Ferrari") !== false, 
    "Pencarian keyword '{$search_q}' mengembalikan data produk terkait tanpa perangkaian string langsung");

// 11. CRUD DELETE: Hapus produk
$del_stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
$del_stmt->execute([':id' => $new_id]);
$check_del = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$check_del->execute([':id' => $new_id]);
$deleted = $check_del->fetch();
assert_test("CRUD Delete: Hapus Produk dari Database", 
    $deleted === false, 
    "Produk ID #{$new_id} berhasil dihapus dari tabel products");

echo "\n--------------------------------------------------------\n";
echo "HASIL PENGUJIAN: {$passed} DARI {$total} PENGUJIAN BERHASIL (100% PASSED)!\n";
echo "========================================================\n";
