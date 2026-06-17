# PHP Development Skill

## Overview
This skill provides guidance for PHP development in the SPMB project.

## When to Use

Use this skill when:
- Creating new PHP files
- Modifying existing PHP logic
- Adding database queries
- Building forms or processing data

## File Patterns

| Pattern | Example |
|---------|---------|
| CRUD operations | `proses_crud.php`, `proses_update.php` |
| Admin pages | `views/database/*.php` |
| Public pages | `public/*.php` |
| TPA pages | `views/tpa/*.php` |
| API endpoints | `views/pendaftaran/api_*.php` |

## Common Patterns

### 1. Form Processing

```php
<?php
// 1. Session first
session_start();

// 2. Include config
require_once __DIR__ . '/../../config.php';

// 3. Validate CSRF
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    die('Invalid request');
}

// 4. Get and validate input
$name = filter_input(INPUT_POST, 'nama', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

if (!$name || !$email) {
    $_SESSION['error'] = 'Data tidak valid';
    header('Location: form.php');
    exit;
}

// 5. Process with prepared statement
$stmt = $conn->prepare("UPDATE siswa SET nama = ?, email = ? WHERE id = ?");
$stmt->bind_param("ssi", $name, $email, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Data berhasil disimpan';
} else {
    $_SESSION['error'] = 'Gagal menyimpan data';
}

header('Location: view.php?id=' . $id);
exit;
```

### 2. Database Query

```php
<?php
// Fetch with joins
function getStudentWithGelombang($id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT s.*, g.nama as gelombang_nama
        FROM siswa s
        LEFT JOIN gelombang g ON s.gelombang_id = g.id
        WHERE s.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

// Insert with transaction
function createStudent($data) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO siswa (nama, email, jurusan, gelombang_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("sssi", 
            $data['nama'], 
            $data['email'], 
            $data['jurusan'],
            $data['gelombang_id']
        );
        $stmt->execute();
        
        $studentId = $conn->insert_id;
        
        // Audit log
        $stmt = $conn->prepare("
            INSERT INTO audit_log (user_id, action, details, timestamp)
            VALUES (?, 'CREATE', ?, NOW())
        ");
        $stmt->bind_param("is", $_SESSION['user_id'], "Created student: $studentId");
        $stmt->execute();
        
        $conn->commit();
        return $studentId;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
```

### 3. API Response

```php
<?php
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new InvalidArgumentException('Invalid JSON');
    }
    
    // Process...
    
    $response['success'] = true;
    $response['data'] = $result;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
```

## Security Reminders

- ✅ Always use prepared statements
- ✅ Always escape output with `htmlspecialchars()`
- ✅ Always validate CSRF token on POST
- ✅ Always use `filter_input()` for user data
- ❌ Never trust user input
- ❌ Never output raw user data

## Testing Checklist

- [ ] Form submission works
- [ ] Validation messages show
- [ ] Database updates correctly
- [ ] Redirect works properly
- [ ] Session messages appear
- [ ] No SQL errors in logs
