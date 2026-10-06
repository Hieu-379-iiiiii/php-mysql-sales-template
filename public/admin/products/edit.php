<?php

$pageTitle = 'Sửa sản phẩm';

require_once '/var/www/src/config/database.php';

$productID = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productID) {
    header('Location: /products/');
    exit;
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

$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: /products/');
    exit;
}

$error = '';
if (isset($_POST['delete_image'])) {
    $imageID = (int) $_POST['delete_image'];

    try {
        $conn->begin_transaction();

        $sqlImage = "
            SELECT
                ProductImageID,
                ImageFile,
                IsPrimary,
                SortOrder
            FROM product_images
            WHERE ProductImageID = ?
              AND ProductID = ?
        ";

        $stmtImage = $conn->prepare($sqlImage);
        $stmtImage->bind_param(
            'ii',
            $imageID,
            $productID
        );
        $stmtImage->execute();

        $imageToDelete =
            $stmtImage->get_result()->fetch_assoc();

        $stmtImage->close();

        if (!$imageToDelete) {
            throw new Exception(
                'Không tìm thấy ảnh cần xóa.'
            );
        }

        $sqlDelete = "
            DELETE FROM product_images
            WHERE ProductImageID = ?
              AND ProductID = ?
        ";

        $stmtDelete = $conn->prepare($sqlDelete);
        $stmtDelete->bind_param(
            'ii',
            $imageID,
            $productID
        );
        $stmtDelete->execute();

        if ($stmtDelete->affected_rows !== 1) {
            throw new Exception(
                'Không thể xóa ảnh.'
            );
        }

        $stmtDelete->close();

        if ((int) $imageToDelete['IsPrimary'] === 1) {
            $sqlNewPrimary = "
                UPDATE product_images
                SET IsPrimary = 1
                WHERE ProductImageID = (
                    SELECT ProductImageID
                    FROM (
                        SELECT ProductImageID
                        FROM product_images
                        WHERE ProductID = ?
                        ORDER BY
                            SortOrder,
                            ProductImageID
                        LIMIT 1
                    ) AS remaining_images
                )
            ";

            $stmtNewPrimary =
                $conn->prepare($sqlNewPrimary);

            $stmtNewPrimary->bind_param(
                'i',
                $productID
            );

            $stmtNewPrimary->execute();
            $stmtNewPrimary->close();
        }

        $deletedSortOrder =
            (int) $imageToDelete['SortOrder'];

        $sqlReorder = "
            UPDATE product_images
            SET SortOrder = SortOrder - 1
            WHERE ProductID = ?
              AND SortOrder > ?
        ";

        $stmtReorder = $conn->prepare($sqlReorder);
        $stmtReorder->bind_param(
            'ii',
            $productID,
            $deletedSortOrder
        );
        $stmtReorder->execute();
        $stmtReorder->close();

        $conn->commit();

        $filePath =
            '/var/www/html/uploads/products/'
            . $imageToDelete['ImageFile'];

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        header(
            'Location: /admin/products/edit.php?id='
            . $productID
            . '&image_deleted=1'
        );
        exit;

    } catch (Throwable $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_primary_image'])) {
        $imageID = (int) $_POST['set_primary_image'];

        try {
            $conn->begin_transaction();

            $sqlResetPrimary = "
                UPDATE product_images
                SET IsPrimary = 0
                WHERE ProductID = ?
            ";

            $stmtResetPrimary = $conn->prepare($sqlResetPrimary);

            if (!$stmtResetPrimary) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }

            $stmtResetPrimary->bind_param('i', $productID);
            $stmtResetPrimary->execute();
            $stmtResetPrimary->close();

            $sqlSetPrimary = "
                UPDATE product_images
                SET IsPrimary = 1
                WHERE ProductImageID = ?
                  AND ProductID = ?
            ";

            $stmtSetPrimary = $conn->prepare($sqlSetPrimary);

            if (!$stmtSetPrimary) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }

            $stmtSetPrimary->bind_param('ii', $imageID, $productID);
            $stmtSetPrimary->execute();

            if ($stmtSetPrimary->affected_rows !== 1) {
                throw new Exception('Không thể đặt ảnh chính.');
            }

            $stmtSetPrimary->close();
            $conn->commit();

            header(
                'Location:/admin/products/edit.php?id='
                . $productID
                . '&primary_updated=1'
            );
            exit;

        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['add_images'])) {
        $files = $_FILES['product_images'] ?? null;

        if (
            !$files
            || !isset($files['name'])
            || !is_array($files['name'])
        ) {
            $error = 'Vui lòng chọn ít nhất một ảnh.';
        } else {
            $maxSize = 2 * 1024 * 1024;

            $extensionMap = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $validImages = [];
            $fileCount = count($files['name']);

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    $error = 'Có lỗi xảy ra khi upload ảnh.';
                    break;
                }

                if ($files['size'][$i] > $maxSize) {
                    $error = 'Mỗi ảnh chỉ được có kích thước tối đa 2 MB.';
                    break;
                }

                $mimeType = $finfo->file($files['tmp_name'][$i]);

                if (!isset($extensionMap[$mimeType])) {
                    $error = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WebP.';
                    break;
                }

                $extension = $extensionMap[$mimeType];

                $fileName =
                    'product-'
                    . bin2hex(random_bytes(8))
                    . '.'
                    . $extension;

                $validImages[] = [
                    'tmp_name'  => $files['tmp_name'][$i],
                    'file_name' => $fileName
                ];
            }

            if (!$error && count($validImages) === 0) {
                $error = 'Vui lòng chọn ít nhất một ảnh.';
            }
        }

        if (!$error) {
            $sqlImageState = "
                SELECT
                    COUNT(*) AS ImageCount,
                    COALESCE(MAX(SortOrder), 0) AS MaxSortOrder
                FROM product_images
                WHERE ProductID = ?
            ";

            $stmtImageState = $conn->prepare($sqlImageState);
            $stmtImageState->bind_param('i', $productID);
            $stmtImageState->execute();

            $imageState = $stmtImageState->get_result()->fetch_assoc();
            $stmtImageState->close();

            $imageCount = (int) $imageState['ImageCount'];
            $nextSortOrder = (int) $imageState['MaxSortOrder'] + 1;

            $movedFiles = [];

            try {
                $conn->begin_transaction();

                $sqlInsertImage = "
                    INSERT INTO product_images
                    (
                        ProductID,
                        ImageFile,
                        AltText,
                        IsPrimary,
                        SortOrder
                    )
                    VALUES (?, ?, ?, ?, ?)
                ";

                $stmtInsertImage = $conn->prepare($sqlInsertImage);

                foreach ($validImages as $index => $image) {
                    $destination =
                        '/var/www/html/uploads/products/'
                        . $image['file_name'];

                    if (!move_uploaded_file(
                        $image['tmp_name'],
                        $destination
                    )) {
                        throw new Exception(
                            'Không thể lưu một trong các ảnh.'
                        );
                    }

                    $movedFiles[] = $destination;

                    $isPrimary =
                        ($imageCount === 0 && $index === 0)
                        ? 1
                        : 0;

                    $sortOrder = $nextSortOrder + $index;

                    $altText =
                        $product['ProductName']
                        . (
                            $isPrimary === 1
                            ? ' - ảnh chính'
                            : ' - ảnh ' . $sortOrder
                        );

                    $stmtInsertImage->bind_param(
                        'issii',
                        $productID,
                        $image['file_name'],
                        $altText,
                        $isPrimary,
                        $sortOrder
                    );

                    if (!$stmtInsertImage->execute()) {
                        throw new Exception(
                            'Không thể lưu thông tin ảnh.'
                        );
                    }
                }

                $stmtInsertImage->close();
                $conn->commit();

                header(
                    'Location: /admin/products/edit.php?id='
                    . $productID
                    . '&images_added=1'
                );
                exit;

            } catch (Throwable $e) {
                $conn->rollback();

                foreach ($movedFiles as $movedFile) {
                    if (file_exists($movedFile)) {
                        unlink($movedFile);
                    }
                }

                $error = $e->getMessage();
            }
        }
    } elseif (isset($_POST['save_product'])) {
        $productCode  = trim($_POST['ProductCode'] ?? '');
        $productName  = trim($_POST['ProductName'] ?? '');
        $description  = trim($_POST['Description'] ?? '');
        $unit         = trim($_POST['Unit'] ?? '');
        $price        = (float) ($_POST['Price'] ?? 0);
        $stockQuantity = (int) ($_POST['StockQuantity'] ?? 0);
        $isActive     = isset($_POST['IsActive']) ? 1 : 0;
        $supplierID   = (int) ($_POST['SupplierID'] ?? 0);
        $categoryID   = (int) ($_POST['CategoryID'] ?? 0);

        if ($productCode === '') {
            $error = 'Mã sản phẩm không được để trống.';
        } elseif ($productName === '') {
            $error = 'Tên sản phẩm không được để trống.';
        } elseif ($price < 0) {
            $error = 'Giá sản phẩm không hợp lệ.';
        } elseif ($stockQuantity < 0) {
            $error = 'Số lượng tồn kho không hợp lệ.';
        } elseif ($categoryID <= 0) {
            $error = 'Vui lòng chọn danh mục.';
        } elseif ($supplierID <= 0) {
            $error = 'Vui lòng chọn nhà cung cấp.';
        } else {
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
                header('Location: /admin/products/');
                exit;
            }

            $error = 'Không thể cập nhật sản phẩm: ' . $stmt->error;
            $stmt->close();
        }
    }
}

$productImagesSql = "
    SELECT
        ProductImageID,
        ProductID,
        ImageFile,
        AltText,
        IsPrimary,
        SortOrder
    FROM product_images
    WHERE ProductID = ?
    ORDER BY SortOrder ASC, ProductImageID ASC
";

$productImagesStmt = $conn->prepare($productImagesSql);

if (!$productImagesStmt) {
    die('Prepare failed: ' . $conn->error);
}

$productImagesStmt->bind_param('i', $productID);
$productImagesStmt->execute();
$productImages = $productImagesStmt->get_result();
$productImagesStmt->close();

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

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';

?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h2>Sửa sản phẩm</h2>

        <a href="/products/" class="btn btn-secondary">
            Quay lại
        </a>

    </div>

    <?php if (
        isset($_GET['primary_updated'])
        && $_GET['primary_updated'] === '1'
    ): ?>
        <div class="alert alert-success">
            Đã cập nhật ảnh chính.
        </div>
    <?php endif; ?>

    <?php if (
        isset($_GET['images_added'])
        && $_GET['images_added'] === '1'
    ): ?>
        <div class="alert alert-success">
            Đã thêm hình ảnh sản phẩm.
        </div>
    <?php endif; ?>

    <?php if (
        isset($_GET['image_deleted'])
        && $_GET['image_deleted'] === '1'
    ): ?>
        <div class="alert alert-success">
            Đã xóa hình ảnh sản phẩm.
        </div>
    <?php endif; ?>

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

            <form method="POST" enctype="multipart/form-data">
                
                <div class="row">
                    <hr class="my-4">
                    <h4 class="mb-3">Hình ảnh sản phẩm</h4>

                    <div class="row">
                        <?php if ($productImages && $productImages->num_rows > 0): ?>
                            <?php while ($image = $productImages->fetch_assoc()): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card h-100">
                                        <img
                                            src="/uploads/products/<?= htmlspecialchars($image['ImageFile']) ?>"
                                            class="card-img-top"
                                            alt="<?= htmlspecialchars($image['AltText'] ?? '') ?>"
                                        >

                                        <div class="card-body">
                                            <small class="text-muted">
                                                Thứ tự: <?= $image['SortOrder'] ?>
                                            </small>

                                            <?php if ((int) $image['IsPrimary'] === 1): ?>
                                                <div class="mt-2">
                                                    <span class="badge bg-success">Ảnh chính</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="mt-2">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-outline-primary btn-sm"
                                                        name="set_primary_image"
                                                        value="<?= (int) $image['ProductImageID'] ?>"
                                                        formaction="/admin/products/edit.php?id=<?= $productID ?>"
                                                        formmethod="post"
                                                    >
                                                        Đặt làm ảnh chính
                                                    </button>
                                                    <button
                                                        type="submit"
                                                        class="btn btn-outline-danger btn-sm ms-2"
                                                        name="delete_image"
                                                        value="<?= $image['ProductImageID'] ?>"
                                                        formaction="/admin/products/edit.php?id=<?= $productID ?>"
                                                        formmethod="post"
                                                        onclick="return confirm('Bạn có chắc muốn xóa ảnh này?');"
                                                    >
                                                        Xóa ảnh
                                                    </button>
                                                </div>
                                                
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-light border mb-0">
                                    Chưa có ảnh nào cho sản phẩm này.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="productImages" class="form-label">
                            Thêm hình ảnh
                        </label>

                        <input
                            type="file"
                            class="form-control"
                            id="productImages"
                            name="product_images[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                        >

                        <div class="form-text">
                            Chấp nhận JPG, PNG hoặc WebP.
                            Mỗi ảnh tối đa 2 MB.
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-outline-success"
                        name="add_images"
                        value="1"
                    >
                        Thêm ảnh
                    </button>
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
                        name="save_product"
                        value="1"
                    >
                        Lưu thay đổi
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php

require_once '/var/www/src/includes/admin/footer.php';

$conn->close();

?>