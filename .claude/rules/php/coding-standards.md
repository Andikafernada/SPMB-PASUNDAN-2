# PHP Coding Rules

## Overview
These rules apply to all PHP files in the SPMB project.

---

## 1. File Structure

Every PHP file must follow this structure:

```php
<?php
/**
 * [File Name]
 * [Description]
 * @package SPMB
 * @author [Name]
 * @version [Version]
 * @date [Date]
 */

// 1. Error reporting (dev only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Requires
require_once __DIR__ . '/../config.php';

// 4. Helper functions (if any)
function helperFunction() {}

// 5. Main logic
// ...

// 6. Output
// ...
```

---

## 2. Naming Conventions

```php
// Variables: snake_case
$student_name = 'John';
$registration_id = 'DA0001';
$created_at = date('Y-m-d H:i:s');

// Constants: SCREAMING_SNAKE_CASE
define('MAX_UPLOAD_SIZE', 5242880);
const STATUS_AKTIF = 'aktif';

// Functions: snake_case
function get_student_by_id($id) {}
function calculate_total_score($answers) {}

// Classes: PascalCase
class StudentModel {}
class WaNotificationService {}

// Private methods/properties: _prefix
class Auth {
    private $_password;
    private function _validateToken() {}
}
```

---

## 3. Prepared Statements (MANDATORY)

```php
// ✅ CORRECT - With mysqli
$stmt = $conn->prepare("SELECT * FROM siswa WHERE id_pendaftaran = ?");
$stmt->bind_param("s", $idDaftar);
$stmt->execute();
$result = $stmt->get_result();

// ✅ CORRECT - With PDO
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id_pendaftaran = :id");
$stmt->execute(['id' => $idDaftar]);
$result = $stmt->fetch();

// ❌ WRONG - String concatenation
$query = "SELECT * FROM siswa WHERE id_pendaftaran = '$idDaftar'";
```

---

## 4. XSS Prevention

```php
// ✅ CORRECT - Always escape output
<?= htmlspecialchars($variable, ENT_QUOTES, 'UTF-8') ?>

// ✅ CORRECT - In PHP
echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

// ❌ WRONG - Direct output
<?= $variable ?>
<?php echo $variable; ?>
```

---

## 5. Input Validation

```php
// Integer validation
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    throw new InvalidArgumentException('Invalid ID');
}

// Email validation
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if ($email === false) {
    die('Email tidak valid');
}

// Custom pattern
if (!preg_match('/^[A-Z]{2}\d{4}$/', $code)) {
    throw new InvalidArgumentException('Invalid code format');
}

// String sanitization
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$name = trim($name);
```

---

## 6. CSRF Protection

```php
// Generate token (in form)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In form
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Validate on submit
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    die('Invalid request');
}
```

---

## 7. Session Security

```php
// Regenerate session ID on login
session_regenerate_id(true);

// Set session cookie params
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// For sensitive operations
$_SESSION['verified'] = true;
```

---

## 8. Error Handling

```php
// Try-catch with specific exceptions
try {
    $stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        throw new RuntimeException('Student not found');
    }
    
    return $stmt->fetch();
    
} catch (RuntimeException $e) {
    error_log('Student lookup failed: ' . $e->getMessage());
    return null;
}

// Custom error handler
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
```

---

## 9. Code Organization

### Function Guidelines
- Max 100 lines per function
- Single responsibility
- Return type hints (PHP 7+)
- Document with PHPDoc

```php
/**
 * Get student by registration ID
 * 
 * @param string $idDaftar Registration ID format XX0000
 * @return array|null Student data or null if not found
 */
function getStudentByIdDaftar(string $idDaftar): ?array {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM siswa WHERE id_pendaftaran = ?");
    $stmt->bind_param("s", $idDaftar);
    $stmt->execute();
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}
```

### File Organization
- Max 500 lines per file
- Group related functions
- Use includes for reusable code
- Clear section separators

```php
// ========================================
// SECTION: Database Functions
// ========================================

function getStudents() {}
function createStudent() {}

// ========================================
// SECTION: Validation Functions
// ========================================

function validateInput() {}
function sanitizeData() {}
```

---

## 10. Security Checklist

Before every commit, verify:

- [ ] All SQL queries use prepared statements
- [ ] All output uses htmlspecialchars()
- [ ] All POST forms have CSRF tokens
- [ ] Passwords use password_hash()/verify()
- [ ] Input validation present on all user data
- [ ] No credentials in code (use .env)
- [ ] Error messages don't leak sensitive info
- [ ] File uploads validated (type, size, name)
