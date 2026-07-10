<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner', 'branch_admin', 'storekeeper']);

$error = '';

if (isPost()) {
    $name = postString('name');
    $contact = postString('contact_person');
    $phone = postString('phone');
    $email = postString('email');
    $address = postString('address');

    if ($name === '') {
        $error = 'Supplier name is required.';
    } else {
        getDb()->prepare(
            'INSERT INTO suppliers (name, contact_person, phone, email, address)
             VALUES (:name, :contact_person, :phone, :email, :address)'
        )->execute([
            'name' => $name,
            'contact_person' => $contact,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
        ]);

        setFlash('success', 'Supplier added.');
        redirect(url('suppliers/index.php'));
    }
}

$pageTitle = 'Add Supplier';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h1 class="h3 mb-4">Add Supplier</h1>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <div class="card"><div class="card-body">
                <form method="post">
                    <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
                    <div class="mb-3"><label class="form-label">Contact Person</label><input class="form-control" name="contact_person"></div>
                    <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
                    <div class="mb-3"><label class="form-label">Address</label><input class="form-control" name="address"></div>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="<?= e(url('suppliers/index.php')) ?>" class="btn btn-secondary">Cancel</a>
                </form>
            </div></div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
