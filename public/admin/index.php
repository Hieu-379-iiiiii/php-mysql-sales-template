<?php

require_once '/var/www/src/config/session.php';
require_once '/var/www/src/config/database.php';

$pageTitle = 'Quản trị hệ thống';

$sqlDashboard = "
    SELECT
        (SELECT COUNT(*) FROM products WHERE IsActive = 1) AS ActiveProducts,
        (SELECT COUNT(*) FROM products) AS TotalProducts,
        (SELECT COUNT(*) FROM categories) AS TotalCategories,
        (SELECT COUNT(*) FROM customers) AS TotalCustomers,
        (SELECT COUNT(*) FROM orders) AS TotalOrders,
        (SELECT COALESCE(SUM(o.TotalAmount), 0) FROM orders o) AS TotalRevenue,
        (SELECT COALESCE(SUM(od.Quantity), 0) FROM orderdetail od) AS TotalUnitsSold,
        (SELECT COUNT(*) FROM orders WHERE Status = 'Pending') AS PendingOrders,
        (SELECT COUNT(*) FROM orders WHERE Status = 'Completed') AS CompletedOrders
";

$sqlRecentOrders = "
    SELECT
        o.OrderID,
        o.OrderDate,
        o.Status,
        o.TotalAmount,
        c.CustomerName,
        (
            SELECT COALESCE(SUM(od2.Quantity), 0)
            FROM orderdetail od2
            WHERE od2.OrderID = o.OrderID
        ) AS TotalItems
    FROM orders o
    INNER JOIN customers c ON c.CustomerID = o.CustomerID
    ORDER BY o.OrderDate DESC
    LIMIT 5
";

$sqlTopProducts = "
    SELECT
        p.ProductName,
        COALESCE(SUM(od.Quantity), 0) AS TotalSold,
        COALESCE(SUM(od.Quantity * od.UnitPrice), 0) AS Revenue
    FROM products p
    LEFT JOIN orderdetail od ON od.ProductID = p.ProductID
    WHERE p.IsActive = 1
    GROUP BY p.ProductID, p.ProductName
    ORDER BY TotalSold DESC, Revenue DESC
    LIMIT 5
";

$dashboard = [];
$summaryResult = $conn->query($sqlDashboard);
if ($summaryResult) {
    $dashboard = $summaryResult->fetch_assoc() ?: [];
    $summaryResult->free();
}

$recentOrders = [];
$ordersResult = $conn->query($sqlRecentOrders);
if ($ordersResult) {
    while ($row = $ordersResult->fetch_assoc()) {
        $recentOrders[] = $row;
    }
    $ordersResult->free();
}

$topProducts = [];
$topProductsResult = $conn->query($sqlTopProducts);
if ($topProductsResult) {
    while ($row = $topProductsResult->fetch_assoc()) {
        $topProducts[] = $row;
    }
    $topProductsResult->free();
}

$conn->close();

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';
?>

<main class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Bảng điều khiển</h1>
            <p class="text-muted mb-0">Tổng quan hoạt động bán hàng và kho hàng.</p>
        </div>
        <span class="badge bg-success-subtle text-success-emphasis px-3 py-2">Hệ thống đang hoạt động</span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Sản phẩm đang bán</div>
                    <div class="fs-3 fw-bold mt-2"><?= (int) ($dashboard['ActiveProducts'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Tổng số đơn hàng</div>
                    <div class="fs-3 fw-bold mt-2"><?= (int) ($dashboard['TotalOrders'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Doanh thu</div>
                    <div class="fs-3 fw-bold mt-2"><?= number_format((float) ($dashboard['TotalRevenue'] ?? 0), 0, ',', '.') ?> đ</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Sản phẩm đã bán</div>
                    <div class="fs-3 fw-bold mt-2"><?= (int) ($dashboard['TotalUnitsSold'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h5 mb-0">Thông tin chung</h2>
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-6">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">Danh mục</div>
                                <div class="fw-bold fs-4"><?= (int) ($dashboard['TotalCategories'] ?? 0) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">Khách hàng</div>
                                <div class="fw-bold fs-4"><?= (int) ($dashboard['TotalCustomers'] ?? 0) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">Đơn chờ xử lý</div>
                                <div class="fw-bold fs-4"><?= (int) ($dashboard['PendingOrders'] ?? 0) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light">
                                <div class="text-muted small">Đã hoàn tất</div>
                                <div class="fw-bold fs-4"><?= (int) ($dashboard['CompletedOrders'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h2 class="h5 mb-0">Top sản phẩm bán chạy</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($topProducts)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topProducts as $index => $product): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="fw-semibold"><?= $index + 1 ?>. <?= htmlspecialchars($product['ProductName']) ?></div>
                                        <small class="text-muted">Đã bán: <?= (int) $product['TotalSold'] ?> sản phẩm</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?= number_format((float) $product['Revenue'], 0, ',', '.') ?> đ</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">Chưa có dữ liệu bán hàng.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Đơn hàng gần đây</h2>
            <a href="/admin/orders/" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($recentOrders)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Ngày</th>
                                <th>Số lượng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?= (int) $order['OrderID'] ?></td>
                                    <td><?= htmlspecialchars($order['CustomerName']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($order['OrderDate'])) ?></td>
                                    <td><?= (int) $order['TotalItems'] ?></td>
                                    <td><?= number_format((float) $order['TotalAmount'], 0, ',', '.') ?> đ</td>
                                    <td>
                                        <span class="badge bg-<?= $order['Status'] === 'Completed' ? 'success' : ($order['Status'] === 'Pending' ? 'warning' : 'secondary') ?> text-dark">
                                            <?= htmlspecialchars($order['Status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-3">
                    <div class="alert alert-info mb-0">Chưa có đơn hàng nào.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</main>

<?php
require_once '/var/www/src/includes/admin/footer.php';
?>