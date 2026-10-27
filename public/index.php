<?php

require_once '/var/www/src/config/session.php';
require_once '/var/www/src/config/database.php';

$pageTitle = 'Trang chủ';

$sqlRecentProducts = "
    SELECT
        p.ProductID,
        p.ProductName,
        p.Price,
        c.CategoryName,
        (
            SELECT pi.ImageFile
            FROM product_images pi
            WHERE pi.ProductID = p.ProductID
              AND pi.IsPrimary = 1
            LIMIT 1
        ) AS ImageFile
    FROM products p
    INNER JOIN categories c ON c.CategoryID = p.CategoryID
    WHERE p.IsActive = 1
    ORDER BY p.ProductID DESC
    LIMIT 4
";

$sqlHotProducts = "
    SELECT
        p.ProductID,
        p.ProductName,
        p.Price,
        c.CategoryName,
        COALESCE(SUM(od.Quantity), 0) AS TotalSold,
        (
            SELECT pi.ImageFile
            FROM product_images pi
            WHERE pi.ProductID = p.ProductID
              AND pi.IsPrimary = 1
            LIMIT 1
        ) AS ImageFile
    FROM products p
    INNER JOIN categories c ON c.CategoryID = p.CategoryID
    LEFT JOIN orderdetail od ON od.ProductID = p.ProductID
    WHERE p.IsActive = 1
    GROUP BY p.ProductID, p.ProductName, p.Price, c.CategoryName
    ORDER BY TotalSold DESC, p.ProductID DESC
    LIMIT 4
";

$sqlStoreSummary = "
    SELECT
        (SELECT COUNT(*) FROM products WHERE IsActive = 1) AS ActiveProducts,
        (SELECT COUNT(*) FROM products) AS TotalProducts,
        (SELECT COUNT(*) FROM categories) AS TotalCategories,
        (SELECT COUNT(*) FROM orders) AS TotalOrders,
        (SELECT COALESCE(SUM(TotalAmount), 0) FROM orders) AS TotalRevenue,
        (SELECT COALESCE(SUM(od.Quantity), 0) FROM orderdetail od) AS TotalUnitsSold
";

$storeSummary = [];
$summaryResult = $conn->query($sqlStoreSummary);
if ($summaryResult) {
    $storeSummary = $summaryResult->fetch_assoc() ?: [];
    $summaryResult->free();
}

$recentProducts = [];
$recentResult = $conn->query($sqlRecentProducts);
if ($recentResult) {
    while ($row = $recentResult->fetch_assoc()) {
        $recentProducts[] = $row;
    }
    $recentResult->free();
}

$hotProducts = [];
$hotResult = $conn->query($sqlHotProducts);
if ($hotResult) {
    while ($row = $hotResult->fetch_assoc()) {
        $hotProducts[] = $row;
    }
    $hotResult->free();
}

$conn->close();

require_once '/var/www/src/includes/frontend/header.php';
require_once '/var/www/src/includes/frontend/navbar.php';
?>

<main class="container py-5">

    <section class="row align-items-center mb-5 g-4">
        <div class="col-lg-7">
            <span class="badge bg-primary-subtle text-primary-emphasis mb-3 px-3 py-2">
                Sales Management System
            </span>
            <h1 class="display-5 fw-bold mb-3">Khám phá sản phẩm mới và bán chạy</h1>
            <p class="lead text-muted mb-4">
                Giao diện cửa hàng giúp bạn theo dõi những sản phẩm gần đây, sản phẩm hot nhất và nhanh chóng truy cập vào chi tiết từng mặt hàng.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="/products.php" class="btn btn-primary btn-lg">Xem tất cả sản phẩm</a>
                <a href="/products-api.php" class="btn btn-outline-primary btn-lg">Dùng API demo</a>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Tổng quan nhanh</h2>
                        <span class="badge bg-success">Online</span>
                    </div>
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">Sản phẩm</div>
                                <div class="fs-4 fw-bold"><?= (int) ($storeSummary['ActiveProducts'] ?? 0) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">Đơn hàng</div>
                                <div class="fs-4 fw-bold"><?= (int) ($storeSummary['TotalOrders'] ?? 0) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">Doanh thu</div>
                                <div class="fs-4 fw-bold"><?= number_format((float) ($storeSummary['TotalRevenue'] ?? 0), 0, ',', '.') ?> đ</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">Đã bán</div>
                                <div class="fs-4 fw-bold"><?= (int) ($storeSummary['TotalUnitsSold'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h3 mb-0">Sản phẩm mới nhất</h2>
            <a href="/products.php" class="btn btn-link p-0">Xem thêm</a>
        </div>

        <?php if (!empty($recentProducts)): ?>
            <div class="row g-4">
                <?php foreach ($recentProducts as $product): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0">
                            <?php if (!empty($product['ImageFile'])): ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 220px;">
                                    <img
                                        src="/uploads/products/<?= htmlspecialchars($product['ImageFile']) ?>"
                                        alt="<?= htmlspecialchars($product['ProductName']) ?>"
                                        style="width: 100%; height: 100%; object-fit: contain;"
                                    >
                                </div>
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 220px;">
                                    Chưa có ảnh
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <small class="text-muted"><?= htmlspecialchars($product['CategoryName']) ?></small>
                                <h3 class="h6 mt-2"><?= htmlspecialchars($product['ProductName']) ?></h3>
                                <p class="fw-bold fs-5 mt-auto mb-3">
                                    <?= number_format((float) $product['Price'], 0, ',', '.') ?> đ
                                </p>
                                <a href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>" class="btn btn-outline-primary btn-sm">
                                    Xem chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Hiện chưa có sản phẩm mới.</div>
        <?php endif; ?>
    </section>

    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h3 mb-0">Sản phẩm hot</h2>
            <span class="badge bg-warning text-dark">Bán chạy</span>
        </div>

        <?php if (!empty($hotProducts)): ?>
            <div class="row g-4">
                <?php foreach ($hotProducts as $product): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0">
                            <?php if (!empty($product['ImageFile'])): ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 220px;">
                                    <img
                                        src="/uploads/products/<?= htmlspecialchars($product['ImageFile']) ?>"
                                        alt="<?= htmlspecialchars($product['ProductName']) ?>"
                                        style="width: 100%; height: 100%; object-fit: contain;"
                                    >
                                </div>
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 220px;">
                                    Chưa có ảnh
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <small class="text-muted"><?= htmlspecialchars($product['CategoryName']) ?></small>
                                <h3 class="h6 mt-2"><?= htmlspecialchars($product['ProductName']) ?></h3>
                                <p class="text-success small mb-2">
                                    Đã bán: <?= (int) $product['TotalSold'] ?> sản phẩm
                                </p>
                                <p class="fw-bold fs-5 mt-auto mb-3">
                                    <?= number_format((float) $product['Price'], 0, ',', '.') ?> đ
                                </p>
                                <a href="/product-detail.php?id=<?= (int) $product['ProductID'] ?>" class="btn btn-primary btn-sm">
                                    Mua ngay
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Chưa có dữ liệu sản phẩm hot.</div>
        <?php endif; ?>
    </section>

</main>

<?php
require_once '/var/www/src/includes/frontend/footer.php';
?>