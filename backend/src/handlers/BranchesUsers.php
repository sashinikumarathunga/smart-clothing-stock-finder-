<?php

declare(strict_types=1);

function handleBranchesList(): never
{
    Auth::requireRole(['owner']);
    $branches = getDb()->query('SELECT * FROM branches ORDER BY name')->fetchAll();
    Response::json(['branches' => $branches]);
}

function handleBranchesCreate(): never
{
    Auth::requireRole(['owner']);
    $body = requestJsonBody();
    $name = bodyString($body, 'name');
    $location = bodyString($body, 'location');
    $phone = bodyString($body, 'phone');

    if ($name === '' || $location === '' || $phone === '') {
        Response::error('Name, location, and phone are required.');
    }

    getDb()->prepare(
        'INSERT INTO branches (name, location, phone) VALUES (:name, :location, :phone)'
    )->execute(['name' => $name, 'location' => $location, 'phone' => $phone]);

    Response::json(['message' => 'Branch created', 'id' => (int) getDb()->lastInsertId()], 201);
}

function handleBranchesGet(int $id): never
{
    Auth::requireRole(['owner']);
    $stmt = getDb()->prepare('SELECT * FROM branches WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $branch = $stmt->fetch();

    if ($branch === false) {
        Response::notFound('Branch not found');
    }

    Response::json(['branch' => $branch]);
}

function handleBranchesUpdate(int $id): never
{
    Auth::requireRole(['owner']);
    $body = requestJsonBody();
    $name = bodyString($body, 'name');
    $location = bodyString($body, 'location');
    $phone = bodyString($body, 'phone');

    if ($name === '' || $location === '' || $phone === '') {
        Response::error('Name, location, and phone are required.');
    }

    $stmt = getDb()->prepare(
        'UPDATE branches SET name = :name, location = :location, phone = :phone WHERE id = :id'
    );
    $stmt->execute(['name' => $name, 'location' => $location, 'phone' => $phone, 'id' => $id]);

    if ($stmt->rowCount() === 0) {
        Response::notFound('Branch not found');
    }

    Response::json(['message' => 'Branch updated']);
}

function handleBranchesDelete(int $id): never
{
    Auth::requireRole(['owner']);
    $stmt = getDb()->prepare('DELETE FROM branches WHERE id = :id');
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        Response::notFound('Branch not found');
    }

    Response::json(['message' => 'Branch deleted']);
}

function handleUsersList(): never
{
    $user = Auth::requireRole(['owner', 'branch_admin']);
    $db = getDb();
    $isOwner = $user['role'] === 'owner';

    if ($isOwner) {
        $stmt = $db->query(
            'SELECT u.id, u.branch_id, u.role, u.full_name, u.username, u.is_active, u.created_at, b.name AS branch_name
             FROM users u
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE u.role = "branch_admin"
             ORDER BY u.full_name'
        );
    } else {
        $stmt = $db->prepare(
            'SELECT u.id, u.branch_id, u.role, u.full_name, u.username, u.is_active, u.created_at, b.name AS branch_name
             FROM users u
             LEFT JOIN branches b ON b.id = u.branch_id
             WHERE u.branch_id = :branch_id AND u.role IN ("storekeeper", "sales_assistant", "cashier")
             ORDER BY u.full_name'
        );
        $stmt->execute(['branch_id' => Auth::requireUserBranchId($user)]);
    }

    Response::json(['users' => $stmt->fetchAll()]);
}

function handleUsersCreate(): never
{
    $user = Auth::requireRole(['owner', 'branch_admin']);
    $db = getDb();
    $isOwner = $user['role'] === 'owner';
    $body = requestJsonBody();

    $fullName = bodyString($body, 'full_name');
    $username = bodyString($body, 'username');
    $password = bodyString($body, 'password');
    $role = bodyString($body, 'role');
    $branchId = $isOwner ? bodyInt($body, 'branch_id') : Auth::requireUserBranchId($user);

    $allowedRoles = $isOwner ? ['branch_admin'] : ['storekeeper', 'sales_assistant', 'cashier'];

    if ($fullName === '' || $username === '' || $password === '' || !in_array($role, $allowedRoles, true)) {
        Response::error('Please fill all required fields correctly.');
    }

    if ($branchId <= 0) {
        Response::error('Branch is required.');
    }

    if (!Auth::canManageRole($user, $role)) {
        Response::forbidden('You cannot create this role.');
    }

    try {
        $db->prepare(
            'INSERT INTO users (branch_id, role, full_name, username, password_hash, is_active)
             VALUES (:branch_id, :role, :full_name, :username, :password_hash, 1)'
        )->execute([
            'branch_id' => $branchId,
            'role' => $role,
            'full_name' => $fullName,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    } catch (PDOException) {
        Response::error('Username already exists.', 409);
    }

    Response::json(['message' => 'User created', 'id' => (int) $db->lastInsertId()], 201);
}

function handleUsersGet(int $id): never
{
    $user = Auth::requireRole(['owner', 'branch_admin']);
    $db = getDb();

    $stmt = $db->prepare(
        'SELECT u.id, u.branch_id, u.role, u.full_name, u.username, u.is_active, u.created_at, b.name AS branch_name
         FROM users u
         LEFT JOIN branches b ON b.id = u.branch_id
         WHERE u.id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    if ($row === false || !userRowAccessible($user, $row)) {
        Response::notFound('User not found');
    }

    Response::json(['user' => $row]);
}

function handleUsersUpdate(int $id): never
{
    $user = Auth::requireRole(['owner', 'branch_admin']);
    $db = getDb();
    $body = requestJsonBody();

    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $existing = $stmt->fetch();

    if ($existing === false || !userRowAccessible($user, $existing)) {
        Response::notFound('User not found');
    }

    $fullName = bodyString($body, 'full_name') ?: $existing['full_name'];
    $username = bodyString($body, 'username') ?: $existing['username'];
    $password = bodyString($body, 'password');
    $isActive = array_key_exists('is_active', $body) ? (bodyInt($body, 'is_active') === 1 ? 1 : 0) : (int) $existing['is_active'];

    $sql = 'UPDATE users SET full_name = :full_name, username = :username, is_active = :is_active';
    $params = [
        'full_name' => $fullName,
        'username' => $username,
        'is_active' => $isActive,
        'id' => $id,
    ];

    if ($password !== '') {
        $sql .= ', password_hash = :password_hash';
        $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= ' WHERE id = :id';

    try {
        $db->prepare($sql)->execute($params);
    } catch (PDOException) {
        Response::error('Username already exists.', 409);
    }

    Response::json(['message' => 'User updated']);
}

function handleUsersDelete(int $id): never
{
    $user = Auth::requireRole(['owner', 'branch_admin']);
    $db = getDb();

    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $existing = $stmt->fetch();

    if ($existing === false || !userRowAccessible($user, $existing)) {
        Response::notFound('User not found');
    }

    if ((int) $existing['id'] === (int) $user['id']) {
        Response::error('You cannot delete your own account.');
    }

    $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    Response::json(['message' => 'User deleted']);
}

function userRowAccessible(array $actor, array $target): bool
{
    if ($actor['role'] === 'owner') {
        return $target['role'] === 'branch_admin';
    }

    if ($actor['role'] === 'branch_admin') {
        return (int) $target['branch_id'] === Auth::requireUserBranchId($actor)
            && in_array($target['role'], ['storekeeper', 'sales_assistant', 'cashier'], true);
    }

    return false;
}
