<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner']);

$error = '';

if (isPost()) {
    $name = postString('name');
    $location = postString('location');
    $phone = postString('phone');

    if ($name === '' || $location === '' || $phone === '') {
        $error = 'All fields are required.';
    } else {
        $stmt = getDb()->prepare(
            'INSERT INTO branches (name, location, phone) VALUES (:name, :location, :phone)'
        );
        $stmt->execute([
            'name' => $name,
            'location' => $location,
            'phone' => $phone,
        ]);

        setFlash('success', 'Branch created successfully.');
        redirect(url('branches/index.php'));
    }
}

$pageTitle = 'Add Branch';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h1 class="h3 mb-4">Add Branch</h1>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label" for="name">Branch Name</label>
                            <input class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="location">Location</label>
                            <input class="form-control" id="location" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="phone">Phone</label>
                            <input class="form-control" id="phone" name="phone" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Branch</button>
                        <a href="<?= e(url('branches/index.php')) ?>" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
