<?php
/**
 * Simple autoloader for the application
 */

spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/src/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Load PHPMailer manually
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    // Create a simple PHPMailer mock for demo purposes
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        class_alias('PHPMailerMock', 'PHPMailer\\PHPMailer\\PHPMailer');
        class_alias('SMTPMock', 'PHPMailer\\PHPMailer\\SMTP');
        class_alias('ExceptionMock', 'PHPMailer\\PHPMailer\\Exception');
    }
}

// Simple PHPMailer mock for demo
class PHPMailerMock {
    public $Host, $SMTPAuth, $Username, $Password, $SMTPSecure, $Port, $CharSet;
    public $Subject, $Body, $AltBody;
    
    public function __construct($exceptions = null) {}
    public function isSMTP() {}
    public function setFrom($address, $name = '') {}
    public function addAddress($address, $name = '') {}
    public function addReplyTo($address, $name = '') {}
    public function isHTML($isHtml = true) {}
    public function send() { return true; }
    public function clearAddresses() {}
}

class SMTPMock {
    const DEBUG_OFF = 0;
}

class ExceptionMock extends Exception {}

// Load JWT manually (simplified)
if (!class_exists('Firebase\\JWT\\JWT')) {
    class JWT {
        public static function encode($payload, $key, $alg = 'HS256') {
            return base64_encode(json_encode($payload));
        }
        
        public static function decode($jwt, $key) {
            return json_decode(base64_decode($jwt));
        }
    }
    
    class Key {
        public function __construct($key, $alg) {}
    }
    
    class_alias('JWT', 'Firebase\\JWT\\JWT');
    class_alias('Key', 'Firebase\\JWT\\Key');
}