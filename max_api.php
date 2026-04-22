<?php
require_once 'config.php';

class MaxAPI {
    private $token;
    private $apiUrl;
    
    public function __construct() {
        $this->token = MAX_BOT_TOKEN;
        $this->apiUrl = MAX_API_URL;
    }
    
    public function sendMessage($chatId, $text, $replyToMessageId = null, $keyboard = null) {
        $data = [
            'chat_id' => (string)$chatId,
            'text' => $text,
            'format' => 'markdown'
        ];
        
        if ($replyToMessageId) {
            $data['reply_to_message_id'] = $replyToMessageId;
        }
        
        if ($keyboard) {
            $data['attachments'] = [
                [
                    'type' => 'inline_keyboard',
                    'payload' => $keyboard
                ]
            ];
        }
        
        $result = $this->call('POST', '/messages', $data);
        
        if (!$result && DEBUG_MODE) {
            bot_log("Failed to send message to {$chatId}");
        }
        
        return $result;
    }
    
    public function sendPhoto($chatId, $photoUrl, $caption = null) {
        $data = [
            'chat_id' => (string)$chatId,
            'text' => $caption ?: '',
            'attachments' => [
                [
                    'type' => 'photo',
                    'payload' => ['url' => $photoUrl]
                ]
            ]
        ];
        
        return $this->call('POST', '/messages', $data);
    }
    
    public function sendVideo($chatId, $videoUrl, $caption = null) {
        $data = [
            'chat_id' => (string)$chatId,
            'text' => $caption ?: '',
            'attachments' => [
                [
                    'type' => 'video',
                    'payload' => ['url' => $videoUrl]
                ]
            ]
        ];
        
        return $this->call('POST', '/messages', $data);
    }
    
    public function getMe() {
        return $this->call('GET', '/me');
    }
    
    public function setWebhook($url) {
        return $this->call('POST', '/webhook', ['url' => $url]);
    }
    
    public function deleteWebhook() {
        return $this->call('DELETE', '/webhook');
    }
    
    public function getWebhookInfo() {
        return $this->call('GET', '/webhook');
    }
    
    private function call($method, $endpoint, $params = []) {
        $url = $this->apiUrl . $endpoint;
        
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $headers = [
            'Authorization: ' . $this->token,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($params)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        if (DEBUG_MODE) {
            bot_log("API Error (HTTP {$httpCode}): " . substr($response, 0, 500));
        }
        
        return false;
    }
    
    public function createInlineKeyboard($buttons) {
        return ['buttons' => $buttons];
    }
    
    public function createCallbackButton($text, $callbackData) {
        return [
            'type' => 'callback',
            'text' => $text,
            'payload' => $callbackData
        ];
    }
}
?>