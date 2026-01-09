<?php
/**
 * Minimal MasterData helper class for CRUD operations on products and departments.
 * This file intentionally keeps dependencies minimal: expects a PDO $db instance
 * available in the global scope (project convention) and returns arrays.
 */
class MasterData
{
    protected $db;

    public function __construct(PDO $db = null)
    {
        if ($db !== null) {
            $this->db = $db;
            return;
        }

        if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof PDO) {
            $this->db = $GLOBALS['db'];
            return;
        }

        // Try to create a PDO instance from common env vars if not provided
        $dsn = getenv('DB_DSN') ?: null;
        $user = getenv('DB_USER') ?: null;
        $pass = getenv('DB_PASS') ?: null;
        if ($dsn) {
            $this->db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } else {
            throw new Exception('No PDO instance available for MasterData');
        }
    }

    // Products
    public function getProducts(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM products ORDER BY name LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProduct(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createProduct(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO products (sku, name, description, unit) VALUES (:sku, :name, :description, :unit)');
        $stmt->execute([
            ':sku' => $data['sku'] ?? null,
            ':name' => $data['name'] ?? '',
            ':description' => $data['description'] ?? '',
            ':unit' => $data['unit'] ?? ''
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateProduct(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE products SET sku = :sku, name = :name, description = :description, unit = :unit WHERE id = :id');
        return $stmt->execute([
            ':sku' => $data['sku'] ?? null,
            ':name' => $data['name'] ?? '',
            ':description' => $data['description'] ?? '',
            ':unit' => $data['unit'] ?? '',
            ':id' => $id
        ]);
    }

    public function deleteProduct(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // Departments
    public function getDepartments(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM departments ORDER BY name LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartment(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM departments WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createDepartment(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO departments (code, name, description) VALUES (:code, :name, :description)');
        $stmt->execute([
            ':code' => $data['code'] ?? null,
            ':name' => $data['name'] ?? '',
            ':description' => $data['description'] ?? ''
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateDepartment(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE departments SET code = :code, name = :name, description = :description WHERE id = :id');
        return $stmt->execute([
            ':code' => $data['code'] ?? null,
            ':name' => $data['name'] ?? '',
            ':description' => $data['description'] ?? '',
            ':id' => $id
        ]);
    }

    public function deleteDepartment(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM departments WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // Users
    public function getUsers(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT id, username, email, full_name, role, created_at FROM users ORDER BY username LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUser(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, username, email, full_name, role, created_at FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createUser(array $data): int
    {
        $passwordHash = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
        $stmt = $this->db->prepare('INSERT INTO users (username, email, password_hash, full_name, role) VALUES (:username, :email, :password_hash, :full_name, :role)');
        $stmt->execute([
            ':username' => $data['username'] ?? '',
            ':email' => $data['email'] ?? '',
            ':password_hash' => $passwordHash,
            ':full_name' => $data['full_name'] ?? '',
            ':role' => $data['role'] ?? 'operator'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateUser(int $id, array $data): bool
    {
        // Selective update: if password is provided, hash it; otherwise omit
        if (!empty($data['password'])) {
            $stmt = $this->db->prepare('UPDATE users SET username = :username, email = :email, password_hash = :password_hash, full_name = :full_name, role = :role WHERE id = :id');
            return $stmt->execute([
                ':username' => $data['username'] ?? '',
                ':email' => $data['email'] ?? '',
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':full_name' => $data['full_name'] ?? '',
                ':role' => $data['role'] ?? 'operator',
                ':id' => $id
            ]);
        }

        $stmt = $this->db->prepare('UPDATE users SET username = :username, email = :email, full_name = :full_name, role = :role WHERE id = :id');
        return $stmt->execute([
            ':username' => $data['username'] ?? '',
            ':email' => $data['email'] ?? '',
            ':full_name' => $data['full_name'] ?? '',
            ':role' => $data['role'] ?? 'operator',
            ':id' => $id
        ]);
    }

    public function deleteUser(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // Production Stages
    public function getStages(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM production_stages ORDER BY stage_order, name LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStage(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM production_stages WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createStage(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO production_stages (name, description, stage_order, department_id) VALUES (:name, :description, :stage_order, :department_id)');
        $stmt->execute([
            ':name' => $data['name'] ?? '',
            ':description' => $data['description'] ?? '',
            ':stage_order' => $data['stage_order'] ?? 0,
            ':department_id' => $data['department_id'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateStage(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE production_stages SET name = :name, description = :description, stage_order = :stage_order, department_id = :department_id WHERE id = :id');
        return $stmt->execute([
            ':name' => $data['name'] ?? '',
            ':description' => $data['description'] ?? '',
            ':stage_order' => $data['stage_order'] ?? 0,
            ':department_id' => $data['department_id'] ?? null,
            ':id' => $id
        ]);
    }

    public function deleteStage(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM production_stages WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}

?>
