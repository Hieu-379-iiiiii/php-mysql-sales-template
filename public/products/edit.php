<?php

$pageTitle = 'Sửa sản phẩm';

require_once '/var/www/src/config/database.php';

$productID = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productID) {
    header('Location: /products/');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productCode  = trim($_POST['ProductCode'] ?? '');
    $productName  = trim($_POST['ProductName'] ?? '');
    $description  = trim($_POST['Description'] ?? '');
    $unit         = trim($_POST['Unit'] ?? '');
    $price        = (float) ($_POST['Price'] ?? 0);
    $stockQuantity = (int) ($_POST['StockQuantity'] ?? 0);
    $isActive     = isset($_POST['IsActive']) ? 1 : 0;
    $supplierID   = (int) ($_POST['SupplierID'] ?? 0);
    $categoryID   = (int) ($_POST['CategoryID'] ?? 0);

    $sql = "
        UPDATE products
        SET
            ProductCode = ?,
            ProductName = ?,
            Description = ?,
            Unit = ?,
            Price = ?,
            StockQuantity = ?,
            IsActive = ?,
            SupplierID = ?,
            CategoryID = ?
        WHERE ProductID = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param(
        'ssssdiiiii',
        $productCode,
        $productName,
        $description,
        $unit,
        $price,
        $stockQuantity,
        $isActive,
        $supplierID,
        $categoryID,
        $productID
    );

    if ($stmt->execute()) {
        $stmt->close();

        header('Location: /products/');
        exit;
    }

    $error = 'Không thể cập nhật sản phẩm: ' . $stmt->error;

    $stmt->close();
}


$sql = "
    SELECT
        ProductID,
        ProductCode,
        ProductName,
        Description,
        Unit,
        Price,
        StockQuantity,
        IsActive,
        SupplierID,
        CategoryID
    FROM products
    WHERE ProductID = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param('i', $productID);
$stmt->execute();

$result = $stmt->get_result();
$product = $result->fetch_assoc();

$stmt->close();

if (!$product) {
    header('Location: /products/');
    exit;
}

$categories = [];

$sql = "
    SELECT
        CategoryID,
        CategoryName
    FROM categories
    ORDER BY CategoryName
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$suppliers = [];

$sql = "
    SELECT
        SupplierID,
        SupplierName
    FROM suppliers
    ORDER BY SupplierName
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}

require_once '/var/www/src/includes/header.php';
require_once '/var/www/src/includes/navbar.php';

?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h2>Sửa sản phẩm</h2>

        <a href="/products/" class="btn btn-secondary">
            Quay lại
        </a>

    </div>

    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="card">

        <div class="card-header">
            <strong>Thông tin sản phẩm</strong>
        </div>

        <div class="card-body">

            <form method="POST">

                <div class="row">

                    <!-- Product Code -->

                    <div class="col-md-6 mb-3">

                        <label for="ProductCode" class="form-label">
                            Mã sản phẩm
                        </label>

                        <input
                            type="text"
                            name="ProductCode"
                            id="ProductCode"
                            class="form-control"
                            value="<?= htmlspecialchars($product['ProductCode'] ?? '') ?>"
                            required
                        >

                    </div>

                    <!-- Product Name -->

                    <div class="col-md-6 mb-3">

                        <label for="ProductName" class="form-label">
                            Tên sản phẩm
                        </label>

                        <input
                            type="text"
                            name="ProductName"
                            id="ProductName"
                            class="form-control"
                            value="<?= htmlspecialchars($product['ProductName'] ?? '') ?>"
                            required
                        >

                    </div>

                </div>

                <!-- Description -->

                <div class="mb-3">

                    <label for="Description" class="form-label">
                        Mô tả
                    </label>

                    <textarea
                        name="Description"
                        id="Description"
                        class="form-control"
                        rows="4"
                    ><?= htmlspecialchars($product['Description'] ?? '') ?></textarea>

                </div>

                <div class="row">

                    <!-- Unit -->

                    <div class="col-md-4 mb-3">

                        <label for="Unit" class="form-label">
                            Đơn vị
                        </label>

                        <input
                            type="text"
                            name="Unit"
                            id="Unit"
                            class="form-control"
                            value="<?= htmlspecialchars($product['Unit'] ?? '') ?>"
                        >

                    </div>

                    <!-- Price -->

                    <div class="col-md-4 mb-3">

                        <label for="Price" class="form-label">
                            Giá
                        </label>

                        <input
                            type="number"
                            name="Price"
                            id="Price"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="<?= htmlspecialchars($product['Price'] ?? 0) ?>"
                            required
                        >

                    </div>

                    <!-- Stock -->

                    <div class="col-md-4 mb-3">

                        <label for="StockQuantity" class="form-label">
                            Tồn kho
                        </label>

                        <input
                            type="number"
                            name="StockQuantity"
                            id="StockQuantity"
                            class="form-control"
                            min="0"
                            value="<?= htmlspecialchars($product['StockQuantity'] ?? 0) ?>"
                            required
                        >

                    </div>

                </div>

                <div class="row">

                    <!-- Category -->

                    <div class="col-md-6 mb-3">

                        <label for="CategoryID" class="form-label">
                            Danh mục
                        </label>

                        <select
                            name="CategoryID"
                            id="CategoryID"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Chọn danh mục --
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= (int) $category['CategoryID'] ?>"
                                    <?= (int) $category['CategoryID'] === (int) $product['CategoryID']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars($category['CategoryName']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- Supplier -->

                    <div class="col-md-6 mb-3">

                        <label for="SupplierID" class="form-label">
                            Nhà cung cấp
                        </label>

                        <select
                            name="SupplierID"
                            id="SupplierID"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Chọn nhà cung cấp --
                            </option>

                            <?php foreach ($suppliers as $supplier): ?>

                                <option
                                    value="<?= (int) $supplier['SupplierID'] ?>"
                                    <?= (int) $supplier['SupplierID'] === (int) $product['SupplierID']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= htmlspecialchars($supplier['SupplierName']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

                <!-- Active -->

                <div class="mb-3">

                    <div class="form-check">

                        <input
                            type="checkbox"
                            name="IsActive"
                            id="IsActive"
                            class="form-check-input"
                            value="1"
                            <?= (int) $product['IsActive'] === 1 ? 'checked' : '' ?>
                        >

                        <label
                            for="IsActive"
                            class="form-check-label"
                        >
                            Đang bán
                        </label>

                    </div>

                </div>

                <hr>

                <div class="d-flex justify-content-end gap-2">

                    <a
                        href="/products/"
                        class="btn btn-secondary"
                    >
                        Hủy
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Lưu thay đổi
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php

require_once '/var/www/src/includes/footer.php';

$conn->close();

?>