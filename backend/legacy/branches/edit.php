<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireRole(['owner']);

$id = (int) ($_GET['id'] ?? 0);
$error = '';

$stmt = getDb()->prepare('SELECT * FROM branches WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$branch = $stmt->fetch();

if ($branch === false) {
    setFlash('danger', 'Branch not found.');
    redirect(url('branches/index.php'));
}

if (isPost()) {
    $name = postString('name');
    $location = postString('location');
    $phone = postString('phone');

    if ($name === '' || $location === '' || $phone === '') {
        $error = 'All fields are required.';
    } else {
        $update = getDb()->prepare(
            'UPDATE branches SET name = :name, location = :location, phone = :phone WHERE id = :id'
        );
        $update->execute([
            'name' => $name,
            'location' => $location,
            'phone' => $phone,
            'id' => $id,
        ]);

        setFlash('success', 'Branch updated successfully.');
        redirect(url('branches/index.php'));
    }
}

$pageTitle = 'Edit Branch';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 p-4">
            <h1 class="h3 mb-4">Edit Branch</h1>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label" for="name">Branch Name</label>
                            <input class="form-control" id="name" name="name" value="<?= e($branch['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="location">Location</label>
                            <input class="form-control" id="location" name="location" value="<?= e($branch['location']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="phone">Phone</label>
                            <input class="form-control" id="phone" name="phone" value="<?= e($branch['phone']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Branch</button>
                        <a href="<?= e(url('branches/index.php')) ?>" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
