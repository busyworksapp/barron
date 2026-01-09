<?php
require_once __DIR__ . '/../../classes/NCRManager.php';
header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    $userId = (int)$_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'];
    $ncr = new NCRManager($GLOBALS['db'] ?? null);
    
    if ($method === 'GET') {
        if (!isset($_GET['ncr_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing ncr_id']);
            exit;
        }
        
        $attachments = $ncr->getAttachments((int)$_GET['ncr_id']);
        echo json_encode(['success' => true, 'data' => $attachments]);
        exit;
    }
    
    if ($method === 'POST') {
        // File upload handling
        if (!isset($_POST['ncr_id']) || empty($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing ncr_id or file']);
            exit;
        }
        
        $ncrId = (int)$_POST['ncr_id'];
        $file = $_FILES['file'];
        
        // Validate file
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($file['type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX']);
            exit;
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10 MB limit
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'File too large. Max 10 MB']);
            exit;
        }
        
        // Save file
        $uploadDir = __DIR__ . '/../../uploads/ncr_attachments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = basename($file['name']);
        $uniqueName = time() . '_' . $filename;
        $filepath = $uploadDir . $uniqueName;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
            exit;
        }
        
        // Save to database
        $attachmentId = $ncr->attachSOP($ncrId, $filename, '/uploads/ncr_attachments/' . $uniqueName, $_POST['description'] ?? null, $userId);
        
        echo json_encode(['success' => true, 'id' => $attachmentId]);
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
