<?php
/**
 * Create Product (Form & Handler dengan Validasi dan Pola PRG)
 * File: create.php
 * Praktikum 3 - Pemrograman Web
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Tangani Pengiriman Form (Method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi Keamanan: Proteksi CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        http_response_code(403);
        die("<h3>Akses Ditolak</h3><p>Token CSRF tidak valid atau sesi Anda telah berakhir.</p>");
    }

    // 2. Ambil dan sanitasi input form dasar
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    // 3. Aturan Validasi Server-Side (Sesuai Checklist Slide 20)
    // - Validasi Nama: minimal 3 karakter
    if (mb_strlen($name) < 3) {
        $errors[] = "Nama produk harus diisi dan minimal 3 karakter.";
    }

    // - Validasi Harga: wajib numerik dan >= 0
    if ($price === '' || !is_numeric($price) || (float)$price < 0) {
        $errors[] = "Harga harus berupa angka valid dan tidak boleh bernilai negatif (>= 0).";
    }

    // - Validasi Stok: wajib integer dan >= 0
    if ($stock === '' || filter_var($stock, FILTER_VALIDATE_INT) === false || (int)$stock < 0) {
        $errors[] = "Stok harus berupa bilangan bulat valid dan tidak boleh bernilai negatif (>= 0).";
    }

    // - Validasi Kategori
    if (empty($category)) {
        $errors[] = "Silakan pilih salah satu kategori produk.";
    }

    // - Validasi Upload Gambar Produk (Fitur Bonus)
    $image_filename = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Terjadi kesalahan saat mengunggah gambar produk.";
        } else {
            // Validasi Ukuran (Maksimal 2MB = 2 * 1024 * 1024 bytes)
            $max_size = 2 * 1024 * 1024;
            if ($file['size'] > $max_size) {
                $errors[] = "Ukuran gambar tidak boleh melebihi 2MB.";
            }

            // Validasi Tipe MIME menggunakan fileinfo
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_mimes)) {
                $errors[] = "Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.";
            }

            // Jika file lolos validasi, generate nama acak aman
            if (empty($errors)) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $image_filename = bin2hex(random_bytes(16)) . '.' . strtolower($extension);
                $upload_path = __DIR__ . '/uploads/' . $image_filename;

                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $errors[] = "Gagal memindahkan berkas gambar ke folder penyimpanan.";
                    $image_filename = null;
                }
            }
        }
    }

    // 4. Pola PRG (Post-Redirect-Get) Jika Terjadi Error Validasi
    if (!empty($errors)) {
        set_old_input([
            'name' => $name,
            'category' => $category,
            'price' => $price,
            'stock' => $stock,
            'description' => $description
        ]);
        set_flash('danger', implode(' ', $errors));
        header("Location: create.php");
        exit;
    }

    // 5. Eksekusi INSERT Menggunakan PDO Prepared Statement (Aman dari SQL Injection)
    try {
        $sql = "INSERT INTO products (name, category, price, stock, description, image) 
                VALUES (:name, :category, :price, :stock, :description, :image)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'        => $name,
            ':category'    => $category,
            ':price'       => (float)$price,
            ':stock'       => (int)$stock,
            ':description' => $description !== '' ? $description : null,
            ':image'       => $image_filename
        ]);

        // Bersihkan old input
        clear_old_input();

        // 6. Pola PRG: Simpan Flash Message Sukses dan Redirect ke index.php
        // Menjamin refresh browser tidak akan menduplikasi entri data
        set_flash('success', "Produk \"{$name}\" berhasil ditambahkan ke katalog!");
        header("Location: index.php");
        exit;

    } catch (PDOException $e) {
        set_flash('danger', "Gagal menyimpan ke database: " . $e->getMessage());
        header("Location: create.php");
        exit;
    }
}

// Render Tampilan Form (Method GET)
$page_title = "Tambah Merchandise F1";
require_once __DIR__ . '/includes/header.php';
$categories = get_product_categories();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🏎️ Tambah Merchandise F1</h1>
        <p class="page-subtitle">Isi data merchandise Formula 1 di bawah ini dengan lengkap dan valid.</p>
    </div>
    <a href="index.php" class="btn btn-outline">&larr; Kembali ke Katalog</a>
</div>

<div class="form-card">
    <form action="create.php" method="POST" enctype="multipart/form-data">
        <!-- Token CSRF -->
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name" class="form-label">Nama Merchandise <span class="required">*</span></label>
            <input type="text" 
                   id="name" 
                   name="name" 
                   class="form-control" 
                   value="<?= e(old('name')) ?>" 
                   placeholder="Contoh: Scuderia Ferrari Team Polo Shirt 2024" 
                   required>
            <div class="form-help">Minimal 3 karakter.</div>
        </div>

        <div class="form-group">
            <label for="category" class="form-label">Kategori <span class="required">*</span></label>
            <select id="category" name="category" class="form-control" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= old('category') === $cat ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-col form-group">
                <label for="price" class="form-label">Harga (Rp) <span class="required">*</span></label>
                <input type="number" 
                       id="price" 
                       name="price" 
                       class="form-control" 
                       value="<?= e(old('price')) ?>" 
                       placeholder="Contoh: 150000" 
                       min="0" 
                       step="1000" 
                       required>
                <div class="form-help">Nilai harga tidak boleh negatif.</div>
            </div>

            <div class="form-col form-group">
                <label for="stock" class="form-label">Jumlah Stok <span class="required">*</span></label>
                <input type="number" 
                       id="stock" 
                       name="stock" 
                       class="form-control" 
                       value="<?= e(old('stock')) ?>" 
                       placeholder="Contoh: 25" 
                       min="0" 
                       step="1" 
                       required>
                <div class="form-help">Jumlah stok minimal 0.</div>
            </div>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Deskripsi Produk</label>
            <textarea id="description" 
                      name="description" 
                      class="form-control" 
                      rows="3" 
                      placeholder="Tuliskan spesifikasi atau keterangan singkat produk..."><?= e(old('description')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="image" class="form-label">Foto Produk (Opsional - Bonus)</label>
            <input type="file" 
                   id="image" 
                   name="image" 
                   class="form-control" 
                   accept="image/jpeg,image/png,image/webp">
            <div class="form-help">Format: JPG, PNG, WEBP. Maksimal ukuran: 2MB.</div>
            <div class="image-preview-box" id="imagePreview">
                <span style="color: var(--text-muted); font-size: 0.8rem;">Preview Foto</span>
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Produk</button>
        </div>
    </form>
</div>

<?php 
// Bersihkan data old input setelah ditampilkan
clear_old_input();
require_once __DIR__ . '/includes/footer.php'; 
?>
