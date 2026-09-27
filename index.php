<?php
/**
 * Read Products (Katalog Flexbox, Pencarian GET, Filter Kategori, & Pagination)
 * File: index.php
 * Praktikum 3 - Pemrograman Web
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Parameter GET untuk Pencarian & Filter Kategori (Bonus Slide 18)
$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

// Parameter Pagination (Bonus)
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 6;
$offset = ($page - 1) * $per_page;

// Menyusun kriteria WHERE secara aman dengan parameter binding PDO
$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(name LIKE :q_name OR description LIKE :q_desc)";
    $params[':q_name'] = '%' . $q . '%';
    $params[':q_desc'] = '%' . $q . '%';
}

if ($category !== '') {
    $where[] = "category = :cat";
    $params[':cat'] = $category;
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // 1. Hitung total baris untuk pagination
    $count_sql = "SELECT COUNT(*) FROM products {$where_clause}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = (int)$count_stmt->fetchColumn();
    $total_pages = max(1, (int)ceil($total_items / $per_page));

    // Koreksi halaman jika melebihi total_pages
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
        $offset = ($page - 1) * $per_page;
    }

    // 2. Query data produk dengan prepared statement
    $sql = "SELECT * FROM products {$where_clause} ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameter pencarian & filter
    foreach ($params as $param_key => $param_val) {
        $stmt->bindValue($param_key, $param_val);
    }
    // Bind parameter limit & offset sebagai integer murni
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $products = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Gagal memuat data produk: " . htmlspecialchars($e->getMessage()));
}

$categories = get_product_categories();
$page_title = "Katalog Merchandise Formula 1";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Halaman -->
<div class="page-header">
    <div>
        <h1 class="page-title">🏎️ F1 Merchandise Store</h1>
        <p class="page-subtitle">Katalog resmi official & fan merchandise tim balap Formula 1 2024.</p>
    </div>
    <div>
        <a href="create.php" class="btn btn-primary">+ Tambah Merchandise</a>
    </div>
</div>

<!-- Filter & Search Bar (Method GET - Slide 18) -->
<div class="filter-card">
    <form method="GET" action="index.php" class="filter-form">
        <div class="search-input-group">
            <input type="text" 
                   name="q" 
                   class="form-control" 
                   placeholder="Cari tim, driver, atau nama merchandise F1..." 
                   value="<?= e($q) ?>">
        </div>
        
        <div class="select-category">
            <select name="category" class="form-control">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                        <?= e($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">🔍 Cari</button>

        <?php if ($q !== '' || $category !== ''): ?>
            <a href="index.php" class="btn btn-outline">Reset Filter</a>
        <?php endif; ?>
    </form>
</div>

<!-- Product Cards Grid (CSS Flexbox - Slide 17 & 20) -->
<?php if (empty($products)): ?>
    <div class="empty-state">
        <div class="empty-icon">🏎️</div>
        <h3>Tidak ada merchandise yang ditemukan</h3>
        <p style="color: var(--text-secondary); margin: 0.5rem 0 1.5rem 0;">
            <?= ($q !== '' || $category !== '') ? 'Coba ubah kata kunci atau filter pencarian merchandise Anda.' : 'Belum ada merchandise di dalam database. Silakan tambahkan merchandise baru.' ?>
        </p>
        <a href="create.php" class="btn btn-primary">+ Tambah Merchandise Pertama</a>
    </div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $p): ?>
            <article class="product-card">
                <!-- Thumbnail Gambar / Placeholder -->
                <div class="card-img-wrapper">
                    <span class="badge badge-blue card-badge-category">
                        <?= e($p['category']) ?>
                    </span>

                    <?php if (!empty($p['image']) && file_exists(__DIR__ . '/uploads/' . $p['image'])): ?>
                        <img src="uploads/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" class="card-img">
                    <?php else: ?>
                        <!-- Fallback Placeholder Bersih -->
                        <span class="card-placeholder-icon">
                            <?php 
                                switch ($p['category']) {
                                    case 'Apparel': echo '👕'; break;
                                    case 'Topi & Cap': echo '🧢'; break;
                                    case 'Diecast & Koleksi': echo '🏎️'; break;
                                    case 'Aksesoris': echo '🎒'; break;
                                    case 'Poster & Seni': echo '🖼️'; break;
                                    default: echo '🏁'; break;
                                }
                            ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Isi Informasi Produk -->
                <div class="card-body">
                    <!-- Escaping htmlspecialchars: Jika ada <b>Promo</b>, akan tampil sebagai teks aman -->
                    <h2 class="card-title"><?= e($p['name']) ?></h2>
                    
                    <p class="card-desc">
                        <?= !empty($p['description']) ? e($p['description']) : '<em>Tidak ada keterangan produk.</em>' ?>
                    </p>

                    <div class="card-meta-row">
                        <span class="card-price"><?= format_rupiah($p['price']) ?></span>

                        <!-- Status Stok Badge -->
                        <?php if ((int)$p['stock'] > 10): ?>
                            <span class="badge badge-green">Stok: <?= (int)$p['stock'] ?></span>
                        <?php elseif ((int)$p['stock'] > 0): ?>
                            <span class="badge badge-amber">Sisa <?= (int)$p['stock'] ?></span>
                        <?php else: ?>
                            <span class="badge badge-red">Stok Habis</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tombol Aksi (Edit & Delete dengan POST + CSRF) -->
                <div class="card-footer">
                    <a href="edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-warning btn-sm">
                        ✏️ Edit
                    </a>
                    
                    <!-- Hapus Menggunakan Method POST & CSRF Token (Bukan Link GET!) -->
                    <form method="POST" action="delete.php" style="margin: 0;" onsubmit="return confirmDelete('<?= e(addslashes($p['name'])) ?>');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">
                            🗑️ Hapus
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!-- Kontrol Pagination Flexbox (Bonus) -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Navigasi Halaman">
            <ul class="pagination">
                <!-- Tombol Sebelumnya -->
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                        &laquo; Prev
                    </a>
                </li>

                <!-- Nomor Halaman -->
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($page === $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <!-- Tombol Berikutnya -->
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                        Next &raquo;
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
