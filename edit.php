<?php


require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'ID produk tidak valid.');
    header("Location: index.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash('danger', 'Produk dengan ID tersebut tidak ditemukan.');
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    die("Gagal memuat produk: " . htmlspecialchars($e->getMessage()));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        http_response_code(403);
        die("<h3>Akses Ditolak</h3><p>Token CSRF tidak valid atau sesi Anda telah berakhir.</p>");
    }

    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    if (mb_strlen($name) < 3) {
        $errors[] = "Nama produk harus diisi dan minimal 3 karakter.";
    }

    if ($price === '' || !is_numeric($price) || (float)$price < 0) {
        $errors[] = "Harga harus berupa angka valid dan tidak boleh bernilai negatif (>= 0).";
    }

    if ($stock === '' || filter_var($stock, FILTER_VALIDATE_INT) === false || (int)$stock < 0) {
        $errors[] = "Stok harus berupa bilangan bulat valid dan tidak boleh bernilai negatif (>= 0).";
    }

    if (empty($category)) {
        $errors[] = "Silakan pilih salah satu kategori produk.";
    }

    $image_filename = $product['image']; 

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Terjadi kesalahan saat mengunggah gambar produk baru.";
        } else {
            $max_size = 2 * 1024 * 1024;
            if ($file['size'] > $max_size) {
                $errors[] = "Ukuran gambar baru tidak boleh melebihi 2MB.";
            }

            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_mimes)) {
                $errors[] = "Format gambar tidak didukung. Gunakan format JPG, PNG, atau WEBP.";
            }

            if (empty($errors)) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_image_filename = bin2hex(random_bytes(16)) . '.' . strtolower($extension);
                $upload_path = __DIR__ . '/uploads/' . $new_image_filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Hapus file gambar lama jika ada
                    if (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image'])) {
                        @unlink(__DIR__ . '/uploads/' . $product['image']);
                    }
                    $image_filename = $new_image_filename;
                } else {
                    $errors[] = "Gagal menyimpan berkas gambar ke folder penyimpanan.";
                }
            }
        }
    }

    if (!empty($errors)) {
        set_old_input([
            'name' => $name,
            'category' => $category,
            'price' => $price,
            'stock' => $stock,
            'description' => $description
        ]);
        set_flash('danger', implode(' ', $errors));
        header("Location: edit.php?id=" . $id);
        exit;
    }

    try {
        $sql = "UPDATE products 
                SET name = :name, 
                    category = :category, 
                    price = :price, 
                    stock = :stock, 
                    description = :description, 
                    image = :image 
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'        => $name,
            ':category'    => $category,
            ':price'       => (float)$price,
            ':stock'       => (int)$stock,
            ':description' => $description !== '' ? $description : null,
            ':image'       => $image_filename,
            ':id'          => $id
        ]);

        clear_old_input();

        set_flash('success', "Data produk \"{$name}\" berhasil diperbarui!");
        header("Location: index.php");
        exit;

    } catch (PDOException $e) {
        set_flash('danger', "Gagal memperbarui data: " . $e->getMessage());
        header("Location: edit.php?id=" . $id);
        exit;
    }
}

$page_title = "Edit Merchandise F1 - " . $product['name'];
require_once __DIR__ . '/includes/header.php';
$categories = get_product_categories();

$val_name = old('name', $product['name']);
$val_category = old('category', $product['category']);
$val_price = old('price', $product['price']);
$val_stock = old('stock', $product['stock']);
$val_description = old('description', $product['description']);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">✏️ Edit Merchandise F1</h1>
        <p class="page-subtitle">Perbarui data merchandise F1 #<?= (int)$product['id'] ?> pada form di bawah ini.</p>
    </div>
    <a href="index.php" class="btn btn-outline">&larr; Kembali ke Katalog</a>
</div>

<div class="form-card">
    <form action="edit.php?id=<?= (int)$product['id'] ?>" method="POST" enctype="multipart/form-data">
    
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">

        <div class="form-group">
            <label for="name" class="form-label">Nama Merchandise <span class="required">*</span></label>
            <input type="text" 
                   id="name" 
                   name="name" 
                   class="form-control" 
                   value="<?= e($val_name) ?>" 
                   required>
            <div class="form-help">Minimal 3 karakter.</div>
        </div>

        <div class="form-group">
            <label for="category" class="form-label">Kategori <span class="required">*</span></label>
            <select id="category" name="category" class="form-control" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $val_category === $cat ? 'selected' : '' ?>>
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
                       value="<?= e($val_price) ?>" 
                       min="0" 
                       step="1000" 
                       required>
                <div class="form-help">Harga tidak boleh bernilai negatif.</div>
            </div>

            <div class="form-col form-group">
                <label for="stock" class="form-label">Jumlah Stok <span class="required">*</span></label>
                <input type="number" 
                       id="stock" 
                       name="stock" 
                       class="form-control" 
                       value="<?= e($val_stock) ?>" 
                       min="0" 
                       step="1" 
                       required>
                <div class="form-help">Stok minimal 0.</div>
            </div>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Deskripsi Produk</label>
            <textarea id="description" 
                      name="description" 
                      class="form-control" 
                      rows="3"><?= e($val_description) ?></textarea>
        </div>

        <div class="form-group">
            <label for="image" class="form-label">Foto Produk (Opsional)</label>
            <input type="file" 
                   id="image" 
                   name="image" 
                   class="form-control" 
                   accept="image/jpeg,image/png,image/webp">
            <div class="form-help">Unggah jika ingin mengganti gambar produk yang lama (Maks. 2MB).</div>

            <div class="image-preview-box" id="imagePreview">
                <?php if (!empty($product['image']) && file_exists(__DIR__ . '/uploads/' . $product['image'])): ?>
                    <img src="uploads/<?= e($product['image']) ?>" alt="Foto Produk">
                <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 0.8rem;">Tanpa Foto</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-outline">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>

<?php 
clear_old_input();
require_once __DIR__ . '/includes/footer.php'; 
?>
