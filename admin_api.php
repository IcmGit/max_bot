<?php
require_once 'db.php';
require_once 'max_api.php';
require_once 'config.php';

$db = Database::getInstance();
$maxApi = new MaxAPI();

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_messages':
        $ticketId = intval($_GET['ticket_id'] ?? 0);
        if (!$ticketId) {
            echo json_encode(['success' => false, 'error' => 'Invalid ticket ID']);
            break;
        }
        
        $messages = $db->getTicketMessages($ticketId);
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;
        
    case 'send_message':
        $ticketId = intval($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        
        if (!$ticketId || empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            break;
        }
        
        $ticket = $db->getTicketById($ticketId);
        if (!$ticket || $ticket['status'] !== 'open') {
            echo json_encode(['success' => false, 'error' => 'Ticket is closed']);
            break;
        }
        
        if ($db->addMessage($ticketId, 'admin', ADMIN_CHAT_ID, $message)) {
            $maxApi->sendMessage($ticket['client_id'], "👨‍💼 *Администратор:*\n\n" . $message);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save message']);
        }
        break;
        
    case 'send_media':
        $ticketId = intval($_POST['ticket_id'] ?? 0);
        $mediaType = $_POST['media_type'] ?? '';
        
        if (!$ticketId || !in_array($mediaType, ['photo', 'video'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            break;
        }
        
        if (!isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'File upload failed']);
            break;
        }
        
        $ticket = $db->getTicketById($ticketId);
        if (!$ticket || $ticket['status'] !== 'open') {
            echo json_encode(['success' => false, 'error' => 'Ticket is closed']);
            break;
        }
        
        $uploadDir = UPLOAD_DIR . ($mediaType == 'photo' ? 'photos/' : 'videos/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        $maxSize = ($mediaType == 'photo') ? 20 * 1024 * 1024 : 50 * 1024 * 1024;
        if ($_FILES['media']['size'] > $maxSize) {
            echo json_encode(['success' => false, 'error' => 'File too large']);
            break;
        }
        
        if (move_uploaded_file($_FILES['media']['tmp_name'], $filePath)) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $mediaUrl = $protocol . $host . '/max_bot/uploads/' . ($mediaType == 'photo' ? 'photos/' : 'videos/') . $fileName;
            
            if ($db->addMessage($ticketId, 'admin', ADMIN_CHAT_ID, null, $mediaUrl, $mediaType)) {
                if ($mediaType == 'photo') {
                    $maxApi->sendPhoto($ticket['client_id'], $mediaUrl, "👨‍💼 *Администратор отправил фото*");
                } else {
                    $maxApi->sendVideo($ticket['client_id'], $mediaUrl, "👨‍💼 *Администратор отправил видео*");
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to save message']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
        }
        break;
        
    case 'close_ticket':
        $ticketId = intval($_GET['ticket_id'] ?? 0);
        if (!$ticketId) {
            echo json_encode(['success' => false, 'error' => 'Invalid ticket ID']);
            break;
        }
        
        $ticket = $db->getTicketById($ticketId);
        if ($db->closeTicket($ticketId)) {
            $message = "🔒 *Заявка #{$ticket['ticket_number']} закрыта*\n\n";
            $message .= "Ваша заявка выполнена. Если у вас возникнут новые вопросы, вы можете создать новую заявку.\n\n";
            $message .= "Спасибо, что пользуетесь нашим сервисом!";
            $maxApi->sendMessage($ticket['client_id'], $message);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to close ticket']);
        }
        break;
        
    case 'mark_read':
        $ticketId = intval($_GET['ticket_id'] ?? 0);
        if ($ticketId) {
            $db->markMessagesAsRead($ticketId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}
?>