<?php

$pageTitle = 'Thêm nhà vận chuyển';

require_once '/var/www/src/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $shipperName = trim($_POST['shipper_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($shipperName === '') {

        $error = 'Tên nhà vận chuyển không được để trống.';

    } else {

        $sql = "
            INSERT INTO shippers (ShipperName, Phone)
            VALUES (?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $error = 'Lỗi prepare SQL: ' . $conn->error;

        } else {

            $stmt->bind_param(
                'ss',
                $shipperName,
                $phone
            );

            if ($stmt->execute()) {

                $stmt->close();

                header('Location: /admin/shipper/');
                exit;

            } else {

                $error = 'Lỗi khi thêm nhà vận chuyển: ' . $stmt->error;

                $stmt->close();
            }
        }
    }
}

require_once '/var/www/src/includes/admin/header.php';
require_once '/var/www/src/includes/admin/navbar.php';

?>

<div class="container mt-4">

    <h2 class="mb-4">Thêm nhà vận chuyển</h2>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post">

        <div class="mb-3">
            <label for="shipperName" class="form-label">
                Tên nhà vận chuyển
            </label>

            <input
                type="text"
                class="form-control"
                id="shipperName"
                name="shipper_name"
                required
                value="<?= htmlspecialchars($_POST['shipper_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">
                Số điện thoại
            </label>

            <input
                type="text"
                class="form-control"
                id="phone"
                name="phone"
                value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>

        <button type="submit" class="btn btn-primary">
            Lưu
        </button>

        <a href="/admin/shipper/" class="btn btn-secondary">
            Hủy
        </a>

    </form>

</div>

<?php
require_once '/var/www/src/includes/admin/footer.php';
?>
