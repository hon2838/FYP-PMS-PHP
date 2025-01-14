<?php
// telegram_bot.php

class TelegramBot {
    private $botToken;
    private $chatId;
    private const TELEGRAM_API = 'https://api.telegram.org/bot';
    private const MAX_RETRIES = 3;
    private const RATE_LIMIT_WINDOW = 60; // 1 minute
    private const MAX_REQUESTS = 30; // Maximum requests per minute

    private $requestCount = 0;
    private $windowStart;

    public function __construct($botToken, $chatId) {
        if (empty($botToken) || empty($chatId)) {
            throw new InvalidArgumentException('Bot token and chat ID are required');
        }
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->windowStart = time();
    }

    private function validateMessage($message) {
        if (empty($message)) {
            throw new InvalidArgumentException('Message cannot be empty');
        }
        if (mb_strlen($message) > 4096) { // Telegram's max message length
            throw new InvalidArgumentException('Message exceeds maximum length of 4096 characters');
        }
    }

    private function checkRateLimit() {
        $now = time();
        if ($now - $this->windowStart >= self::RATE_LIMIT_WINDOW) {
            $this->windowStart = $now;
            $this->requestCount = 0;
        }
        if ($this->requestCount >= self::MAX_REQUESTS) {
            throw new RuntimeException('Rate limit exceeded');
        }
        $this->requestCount++;
    }

    // Send text message with optional formatting
    public function sendMessage($message, $parse_mode = 'HTML', $disable_notification = false) {
        try {
            $this->validateMessage($message);
            $this->checkRateLimit();

            $data = [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => $parse_mode,
                'disable_notification' => $disable_notification
            ];
            
            return $this->makeRequest('sendMessage', $data);
        } catch (Exception $e) {
            error_log("Telegram sendMessage error: " . $e->getMessage());
            return false;
        }
    }

    // Send alert for new paperwork submission
    public function sendPaperworkAlert($ppw_id, $user_name, $title) {
        $message = "🔔 *New Paperwork Submission*\n\n" .
                  "ID: `$ppw_id`\n" .
                  "From: $user_name\n" .
                  "Title: $title\n" .
                  "Time: " . date('Y-m-d H:i:s');
        
        return $this->sendMessage($message, 'Markdown');
    }

    // Send login failure alert
    public function sendLoginAlert($email, $ip, $attempt_count) {
        $message = "⚠️ *Failed Login Attempt*\n\n" .
                  "Email: `$email`\n" .
                  "IP: `$ip`\n" .
                  "Attempt #: $attempt_count\n" .
                  "Time: " . date('Y-m-d H:i:s');
        
        return $this->sendMessage($message, 'Markdown');
    }

    // Send system error alert
    public function sendErrorAlert($error_type, $message, $file = null, $line = null) {
        $alert = "🚨 *System Error*\n\n" .
                "Type: `$error_type`\n" .
                "Message: `$message`\n";
        
        if ($file && $line) {
            $alert .= "Location: `$file:$line`\n";
        }
        
        $alert .= "Time: " . date('Y-m-d H:i:s');
        
        return $this->sendMessage($alert, 'Markdown', false);
    }

    // Make HTTP request to Telegram API with retry mechanism
    private function makeRequest($method, $data, $attempts = 0) {
        $url = self::TELEGRAM_API . $this->botToken . '/' . $method;
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ];

        try {
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            if ($result === false && $attempts < self::MAX_RETRIES) {
                sleep(1); // Wait before retry
                return $this->makeRequest($method, $data, $attempts + 1);
            }
            
            return json_decode($result, true);
        } catch (Exception $e) {
            error_log("Telegram API Error: " . $e->getMessage());
            return false;
        }
    }
}