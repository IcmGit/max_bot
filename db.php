<?php
require_once 'config.php';

class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset(DB_CHARSET);
            $this->connection->query("SET NAMES '" . DB_CHARSET . "' COLLATE '" . DB_COLLATE . "'");
            $this->connection->query("SET time_zone = '+03:00'"); // Московское время
            
            bot_log("Database connected successfully");
        } catch (Exception $e) {
            bot_log("DB Error: " . $e->getMessage());
            die("Database connection error");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function getNextTicketNumber() {
        $this->connection->begin_transaction();
        
        $stmt = $this->connection->prepare("SELECT setting_value FROM settings WHERE setting_key = 'last_ticket_number' FOR UPDATE");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $nextNumber = ($row['setting_value'] ?? 0) + 1;
        
        $stmt = $this->connection->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'last_ticket_number'");
        $stmt->bind_param("i", $nextNumber);
        $stmt->execute();
        
        $this->connection->commit();
        
        return $nextNumber;
    }
    
    public function createTicket($clientId, $clientName, $clientPhone, $requestText) {
        $ticketNumber = $this->getNextTicketNumber();
        $stmt = $this->connection->prepare(
            "INSERT INTO tickets (ticket_number, client_id, client_name, client_phone, request_text, status, created_at) 
             VALUES (?, ?, ?, ?, ?, 'open', NOW())"
        );
        $stmt->bind_param("iisss", $ticketNumber, $clientId, $clientName, $clientPhone, $requestText);
        
        if ($stmt->execute()) {
            return $this->connection->insert_id;
        }
        bot_log("Create ticket error: " . $stmt->error);
        return false;
    }
    
    public function addMessage($ticketId, $senderType, $senderId, $messageText = null, $mediaUrl = null, $mediaType = 'none') {
        if (empty($messageText) && empty($mediaUrl)) {
            return false;
        }
        
        $stmt = $this->connection->prepare(
            "INSERT INTO messages (ticket_id, sender_type, sender_id, message_text, media_url, media_type, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param("isssss", $ticketId, $senderType, $senderId, $messageText, $mediaUrl, $mediaType);
        
        if ($stmt->execute()) {
            return $this->connection->insert_id;
        }
        bot_log("Add message error: " . $stmt->error);
        return false;
    }
    
    public function getTicketMessages($ticketId) {
        $stmt = $this->connection->prepare(
            "SELECT * FROM messages WHERE ticket_id = ? ORDER BY created_at ASC"
        );
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);
        
        foreach ($messages as &$message) {
            $message['message_text'] = htmlspecialchars($message['message_text'], ENT_QUOTES, 'UTF-8');
        }
        
        return $messages;
    }
    
    public function getTicketById($ticketId) {
        $stmt = $this->connection->prepare("SELECT * FROM tickets WHERE id = ?");
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getClientActiveTicket($clientId) {
        $stmt = $this->connection->prepare(
            "SELECT * FROM tickets WHERE client_id = ? AND status = 'open' ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function closeTicket($ticketId) {
        $stmt = $this->connection->prepare("UPDATE tickets SET status = 'closed', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $ticketId);
        return $stmt->execute();
    }
    
    public function getAllOpenTickets() {
        $result = $this->connection->query(
            "SELECT t.*, 
             (SELECT COUNT(*) FROM messages WHERE ticket_id = t.id AND is_read = FALSE AND sender_type = 'client') as unread_count
             FROM tickets t 
             WHERE t.status = 'open' 
             ORDER BY t.created_at DESC"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function markMessagesAsRead($ticketId) {
        $stmt = $this->connection->prepare("UPDATE messages SET is_read = TRUE WHERE ticket_id = ? AND sender_type = 'client'");
        $stmt->bind_param("i", $ticketId);
        return $stmt->execute();
    }
}
?>