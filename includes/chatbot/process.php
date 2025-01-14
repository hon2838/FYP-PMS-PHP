<?php
require_once '../auth.php';
require_once 'chatbot.php';

header('Content-Type: application/json');

try {
    // Validate session
    $auth = Auth::getInstance();
    $auth->validateSession();
    
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['message'])) {
        throw new Exception('No message provided');
    }
    
    // Process message
    $chatbot = new Chatbot($auth->getConnection(), $auth->getUserId());
    $response = $chatbot->processMessage($data['message']);
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}