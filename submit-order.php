<?php
/**
 * CLEM GEO ENTERPRISE - Order Submission Handler
 * Receives order form data, sends SMS to admin, and returns JSON response
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// ============================================
// CONFIGURATION
// ============================================

// Admin phone number (Ghana format)
define('ADMIN_PHONE', '0541261111');

// Company name for SMS signature
define('COMPANY_NAME', 'CLEM GEO');

// SMS Provider Configuration
// Supported: 'termii', 'africastalking', 'twilio', 'vonage', 'demo'
define('SMS_PROVIDER', 'demo');

// API Credentials (fill these in when you have an account)
define('SMS_API_KEY', 'YOUR_API_KEY_HERE');
define('SMS_API_SECRET', 'YOUR_API_SECRET_HERE');
define('SMS_SENDER_ID', 'CLEMGEO');

// Demo mode: logs SMS instead of sending (set to false when using real provider)
define('DEMO_MODE', true);

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Send SMS using Termii (popular in Ghana/Nigeria)
 * Sign up: https://termii.com
 */
function sendSmsTermii($phone, $message) {
    $url = 'https://api.ng.termii.com/api/sms/send';
    
    $data = [
        'to' => formatPhoneNumber($phone),
        'from' => SMS_SENDER_ID,
        'sms' => $message,
        'type' => 'plain',
        'channel' => 'generic',
        'api_key' => SMS_API_KEY
    ];
    
    return makeHttpPost($url, $data);
}

/**
 * Send SMS using Africa's Talking
 * Sign up: https://africastalking.com
 */
function sendSmsAfricaTalking($phone, $message) {
    $url = 'https://api.africastalking.com/version1/messaging';
    
    $headers = [
        'apiKey: ' . SMS_API_KEY,
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ];
    
    $data = [
        'username' => SMS_API_SECRET,
        'to' => formatPhoneNumber($phone),
        'message' => $message,
        'from' => SMS_SENDER_ID
    ];
    
    return makeHttpPost($url, $data, $headers);
}

/**
 * Send SMS using Twilio
 * Sign up: https://twilio.com
 */
function sendSmsTwilio($phone, $message) {
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . SMS_API_KEY . '/Messages.json';
    
    $data = [
        'To' => formatPhoneNumberInternational($phone),
        'From' => SMS_SENDER_ID,
        'Body' => $message
    ];
    
    $auth = base64_encode(SMS_API_KEY . ':' . SMS_API_SECRET);
    $headers = [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/x-www-form-urlencoded'
    ];
    
    return makeHttpPost($url, $data, $headers);
}

/**
 * Send SMS using Vonage (Nexmo)
 * Sign up: https://vonage.com
 */
function sendSmsVonage($phone, $message) {
    $url = 'https://rest.nexmo.com/sms/json';
    
    $data = [
        'api_key' => SMS_API_KEY,
        'api_secret' => SMS_API_SECRET,
        'to' => formatPhoneNumberInternational($phone),
        'from' => SMS_SENDER_ID,
        'text' => $message
    ];
    
    return makeHttpPost($url, $data);
}

/**
 * Demo mode - logs SMS to file instead of sending
 */
function sendSmsDemo($phone, $message) {
    $logEntry = sprintf(
        "[%s]\nTo: %s\nMessage: %s\nProvider: %s\nStatus: SIMULATED (Demo Mode)\n-------------------\n",
        date('Y-m-d H:i:s'),
        $phone,
        $message,
        SMS_PROVIDER
    );
    
    $logFile = __DIR__ . '/sms-log.txt';
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    return [
        'success' => true,
        'message' => 'SMS logged in demo mode. Check sms-log.txt',
        'demo' => true
    ];
}

/**
 * Make HTTP POST request
 */
function makeHttpPost($url, $data, $headers = []) {
    $ch = curl_init();
    
    $defaultHeaders = ['Content-Type: application/x-www-form-urlencoded'];
    $allHeaders = array_merge($defaultHeaders, $headers);
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'cURL Error: ' . $error];
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'response' => json_decode($response, true)];
    }
    
    return ['success' => false, 'error' => 'HTTP ' . $httpCode, 'response' => $response];
}

/**
 * Format Ghana phone number for local SMS
 */
function formatPhoneNumber($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Handle Ghana numbers
    if (strlen($phone) === 9) {
        // Add Ghana country code
        return '233' . $phone;
    }
    
    if (strlen($phone) === 10 && $phone[0] === '0') {
        // Replace leading 0 with 233
        return '233' . substr($phone, 1);
    }
    
    if (strlen($phone) === 12 && substr($phone, 0, 3) === '233') {
        return $phone;
    }
    
    return $phone;
}

/**
 * Format phone number with + for international
 */
function formatPhoneNumberInternational($phone) {
    $formatted = formatPhoneNumber($phone);
    return '+' . $formatted;
}

/**
 * Build SMS message from order data
 */
function buildSmsMessage($data) {
    $name = $data['name'] ?? 'Unknown';
    $phone = $data['phone'] ?? 'N/A';
    $orderDetails = $data['orderDetails'] ?? 'No details';
    
    // Truncate order details if too long for SMS
    if (strlen($orderDetails) > 100) {
        $orderDetails = substr($orderDetails, 0, 97) . '...';
    }
    
    $message = sprintf(
        "NEW ORDER - %s\nName: %s\nPhone: %s\nDetails: %s\nReply to confirm.",
        COMPANY_NAME,
        $name,
        $phone,
        $orderDetails
    );
    
    return $message;
}

/**
 * Send email fallback notification
 */
function sendEmailNotification($data) {
    $to = 'bro.bombish@yahoo.com'; // Admin email for order notifications
    $subject = 'New Order - CLEM GEO ENTERPRISE';
    
    $message = "New order received:\n\n";
    $message .= "Name: " . ($data['name'] ?? 'N/A') . "\n";
    $message .= "Phone: " . ($data['phone'] ?? 'N/A') . "\n";
    $message .= "Order Details:\n" . ($data['orderDetails'] ?? 'N/A') . "\n\n";
    $message .= "Submitted at: " . date('Y-m-d H:i:s') . "\n";
    
    $headers = 'From: noreply@clemgeo.com' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// ============================================
// MAIN HANDLER
// ============================================

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
        exit;
    }
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Fallback to $_POST if JSON decode fails
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate required fields
    $required = ['name', 'phone', 'orderDetails'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
            exit;
        }
    }
    
    // Sanitize inputs
    $name = htmlspecialchars(trim($input['name']), ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars(trim($input['phone']), ENT_QUOTES, 'UTF-8');
    $orderDetails = htmlspecialchars(trim($input['orderDetails']), ENT_QUOTES, 'UTF-8');
    
    $cleanData = [
        'name' => $name,
        'phone' => $phone,
        'orderDetails' => $orderDetails
    ];
    
    // Build SMS message
    $smsMessage = buildSmsMessage($cleanData);
    
    // Send SMS based on configured provider
    $smsResult = null;
    
    if (DEMO_MODE) {
        $smsResult = sendSmsDemo(ADMIN_PHONE, $smsMessage);
    } else {
        switch (strtolower(SMS_PROVIDER)) {
            case 'termii':
                $smsResult = sendSmsTermii(ADMIN_PHONE, $smsMessage);
                break;
            case 'africastalking':
                $smsResult = sendSmsAfricaTalking(ADMIN_PHONE, $smsMessage);
                break;
            case 'twilio':
                $smsResult = sendSmsTwilio(ADMIN_PHONE, $smsMessage);
                break;
            case 'vonage':
                $smsResult = sendSmsVonage(ADMIN_PHONE, $smsMessage);
                break;
            default:
                $smsResult = sendSmsDemo(ADMIN_PHONE, $smsMessage);
        }
    }
    
    // Send email fallback
    $emailSent = sendEmailNotification($cleanData);
    
    // Log order to file
    $orderLog = sprintf(
        "[%s] Order from %s (%s)\nDetails: %s\nSMS: %s | Email: %s\n-------------------\n",
        date('Y-m-d H:i:s'),
        $name,
        $phone,
        $orderDetails,
        $smsResult['success'] ? 'Sent' : 'Failed',
        $emailSent ? 'Sent' : 'Failed'
    );
    file_put_contents(__DIR__ . '/orders-log.txt', $orderLog, FILE_APPEND | LOCK_EX);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order submitted successfully!',
        'sms' => [
            'sent' => $smsResult['success'],
            'demo' => DEMO_MODE,
            'provider' => SMS_PROVIDER,
            'admin_phone' => ADMIN_PHONE,
            'details' => $smsResult['demo'] ? 'SMS simulated in demo mode' : ($smsResult['success'] ? 'SMS sent to admin' : 'SMS failed: ' . ($smsResult['error'] ?? 'Unknown error'))
        ],
        'email' => [
            'sent' => $emailSent
        ],
        'whatsapp_link' => 'https://wa.me/233247877429?text=' . urlencode(
            "Hello CLEM GEO!\n\nNew Order:\nName: $name\nPhone: $phone\nDetails: $orderDetails"
        )
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

