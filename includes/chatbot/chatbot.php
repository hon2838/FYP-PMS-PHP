<?php
class Chatbot {
    private $conn;
    private $user_id;
    private $context = [];
    
    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->loadContext();
    }
    
    private function loadContext() {
        // Load user context from database
        $stmt = $this->conn->prepare("
            SELECT role, department, permissions 
            FROM tbl_users 
            WHERE id = ?
        ");
        $stmt->execute([$this->user_id]);
        $this->context = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function processMessage($message) {
        // Log the conversation
        $this->logConversation($message);
        
        // Process based on keywords
        $response = $this->generateResponse($message);
        
        return [
            'message' => $response,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function generateResponse($message) {
        $message = strtolower($message);
        
        // Basic response patterns
        $patterns = [
            'help' => "I can help you with:\n- Paperwork submission process\n- System navigation\n- Common issues\n- Department contacts",
            'submit' => "To submit paperwork:\n1. Go to Dashboard\n2. Click 'New Paperwork'\n3. Fill the required fields\n4. Upload your document\n5. Submit for review",
            'status' => "You can check your paperwork status in the Dashboard under 'My Submissions'.",
            'contact' => "For technical support, please contact the system administrator or your department HOD."
        ];
        
        foreach ($patterns as $keyword => $response) {
            if (strpos($message, $keyword) !== false) {
                return $response;
            }
        }
        
        return "I'm not sure about that. Try asking about help, submitting paperwork, checking status, or contacting support.";
    }
    
    private function logConversation($message) {
        $stmt = $this->conn->prepare("
            INSERT INTO chat_logs (user_id, message, timestamp)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$this->user_id, $message]);
    }
}