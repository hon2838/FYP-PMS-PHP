<?php
require_once 'telegram_bot.php';

function initTelegramBot() {
    $botToken = getenv('TELEGRAM_BOT_TOKEN');
    $chatId = getenv('TELEGRAM_CHAT_ID');
    
    // Validate environment variables
    if (empty($botToken) || $botToken === 'your_bot_token_here') {
        error_log('Telegram bot token not configured');
        return null;
    }
    
    if (empty($chatId) || $chatId === 'your_chat_id_here') {
        error_log('Telegram chat ID not configured');
        return null;
    }
    
    try {
        return new TelegramBot($botToken, $chatId);
    } catch (Exception $e) {
        error_log('Failed to initialize Telegram bot: ' . $e->getMessage());
        return null;
    }
}

// Paperwork notification handler
function notifyPaperworkSubmission($ppw_id, $user_name, $title) {
    if (getenv('TELEGRAM_NOTIFICATION_ENABLED') !== 'false') {
        $bot = initTelegramBot();
        if ($bot) {
            return $bot->sendPaperworkAlert($ppw_id, $user_name, $title);
        }
    }
    return false;
}

// Login failure handler
function notifyLoginFailure($email, $ip, $attempt_count) {
    if (getenv('TELEGRAM_NOTIFICATION_ENABLED') !== 'false') {
        $bot = initTelegramBot();
        return $bot->sendLoginAlert($email, $ip, $attempt_count);
    }
    return false;
}

// System error handler
function notifySystemError($error_type, $message, $file = null, $line = null) {
    if (getenv('TELEGRAM_NOTIFICATION_ENABLED') !== 'false' && 
        getenv('TELEGRAM_ALERT_LEVEL') === 'error') {
        $bot = initTelegramBot();
        if ($bot) {
            try {
                return $bot->sendErrorAlert($error_type, $message, $file, $line);
            } catch (Exception $e) {
                error_log('Failed to send error notification: ' . $e->getMessage());
            }
        }
    }
    return false;
}