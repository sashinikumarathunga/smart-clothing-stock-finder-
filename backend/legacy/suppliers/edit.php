<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner', 'branch_admin', 'storekeeper']);

$id = (int) ($_GET['id'] ?? 0);
$error = '';

$stmt = getDb()->prepare('SELECT * FROM suppliers WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$supplier = $stmt->fetch();

if ($supplier === false) {
    setFlash('danger', 'Supplier not found.');
    redirect(url('suppliers/index.php'));
}

if (isPost()) {
    $name = postString('name');
    if ($name === '') {
        $error = 'Supplier name is required.';
    } else {
        getDb()->prepare(
            'UPDATE suppliers SET name = :name, contact_person = :contact_person, phone = :phone, email = :email, address = :address WHERE id = :id'
        )->execute([
            'name' => $name,
            'contact_person' => postString('contact_person'),
            'phone' => postString('phone'),
            'email' => postString('email'),
            'address' => postString('address'),
            'id' => $id,
        ]);
        setFlash('success', 'Supplier updated.');
        redirect(url('suppliers/index.php'));
    }
}

$pageTitle = 'Edit Supplier';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h1 class="h3 mb-4">Edit Supplier</h1>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <div class="card"><div class="card-body">
                <form method="post">
                    <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= e($supplier['name']) ?>" required></div>
                    <div class="mb-3"><label class="form-label">Contact Person</label><input class="form-control" name="contact_person" value="<?= e($supplier['contact_person']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= e($supplier['phone']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= e($supplier['email']) ?>"></div>
                    <div class="mb-3"><label class="form-label">Address</label><input class="form-control" name="address" value="<?= e($supplier['address']) ?>"></div>
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="<?= e(url('suppliers/index.php')) ?>" class="btn btn-secondary">Cancel</a>
                </form>
            </div></div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
