<?php
require_once __DIR__ . '/../../classes/MasterData.php';
header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE) session_start();

    // RBAC guard: require admin for writes; planners/managers can view
    $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    $canView = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['planner', 'manager', 'admin'], true);

    $method = $_SERVER['REQUEST_METHOD'];
    $md = new MasterData($GLOBALS['db'] ?? null);

    if ($method === 'GET') {
        if (!$canView) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden: view permission required']);
            exit;
        }

        if (isset($_GET['id'])) {
            $item = $md->getDepartment((int)$_GET['id']);
            echo json_encode(['success' => true, 'data' => $item]);
            exit;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $rows = $md->getDepartments($limit, $offset);
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden: admin role required']);
            exit;
        }

        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $payload = $decoded;
        }

        if (isset($payload['id']) && !empty($payload['id'])) {
            $id = (int)$payload['id'];
            $ok = $md->updateDepartment($id, $payload);
            echo json_encode(['success' => (bool)$ok]);
            exit;
        }

        $newId = $md->createDepartment($payload);
        echo json_encode(['success' => true, 'id' => $newId]);
        exit;
    }

    if ($method === 'DELETE') {
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden: admin role required']);
            exit;
        }

        parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
        if (empty($qs['id'])) {
            http_response_code(400);
            echo json_encode(['success'=>false,'error'=>'Missing id']);
            exit;
        }
        $ok = $md->deleteDepartment((int)$qs['id']);
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
