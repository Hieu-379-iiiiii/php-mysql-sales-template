<?php

require_once '/var/www/src/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /products/');
    exit;
}

$productID = isset($_POST['id'])
    ? (int) $_POST['id']
    : 0;

if ($productID <= 0) {
    header('Location: /products/');
    exit;
}

try {

    $conn->begin_transaction();

    $sql = "
        DELETE FROM product_images
        WHERE ProductID = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Không thể prepare câu lệnh xóa ảnh.');
    }

    $stmt->bind_param('i', $productID);
    $stmt->execute();
    $stmt->close();

    $sql = "
        DELETE FROM products
        WHERE ProductID = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Không thể prepare câu lệnh xóa sản phẩm.');
    }

    $stmt->bind_param('i', $productID);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Không tìm thấy sản phẩm.');
    }

    $stmt->close();

    $conn->commit();
    $conn->close();

    header('Location: /products/');
    exit;

} catch (Throwable $e) {

    $conn->rollback();
    $conn->close();

    die('Không thể xóa sản phẩm: ' . htmlspecialchars($e->getMessage()));
}