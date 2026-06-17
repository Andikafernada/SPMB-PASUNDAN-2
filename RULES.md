# RULES.md - Coding Rules and Guidelines

## Overview

This file defines the coding rules and guidelines for the SPMB project. These rules ensure consistency, security, and maintainability across the codebase.

---

## I. Security Rules (MANDATORY)

### A. SQL Injection Prevention

**RULE:** ALL database queries MUST use prepared statements.

```php
// ✅ CORRECT - Prepared Statement
$stmt = $conn->prepare("SELECT * FROM siswa WHERE id_pendaftaran = ?");
$stmt->bind_param("s", $idDaftar);
$stmt->execute();
$result = $stmt->get_result();

// ❌ WRONG - String concatenation (VULNERABLE)
$query = "SELECT * FROM siswa WHERE id_pendaftaran = '$idDaftar'";
$result = mysqli_query($conn, $query);
```

**Exception:** Queries without user input may use direct query (e.g., `SELECT * FROM gelombang WHERE aktif = 1`)

### B. XSS Prevention

**RULE:** ALL output that contains user input MUST be escaped.

```php
// ✅ CORRECT
echo htmlspecialchars($namaSiswa, ENT_QUOTES, 'UTF-8');

// ❌ WRONG - Direct output
echo $namaSiswa;
```

**When to escape:**
- User-provided form data
- Database content that may contain HTML
- URL parameters
- Session data displayed to users

### C. CSRF Protection

**RULE:** ALL POST/PUT/DELETE requests MUST validate CSRF token.

```php
// Form generation
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In form
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Form processing
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Invalid CSRF token');
}
```

### D. Password Security

**RULE:** Passwords MUST use bcrypt hashing.

```php
// ✅ CORRECT - Hashing
$hash = password_hash($password, PASSWORD_DEFAULT);

// ✅ CORRECT - Verification
if (password_verify($input, $storedHash)) {
    // Login success
}
```

### E. Input Validation

**RULE:** ALL user input MUST be validated before use.

```php
// String validation
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    die('Invalid ID');
}

// Email validation
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if ($email === false) {
    die('Invalid email');
}

// Custom pattern
if (!preg_match('/^[A-Z]{2}\d{4}$/', $kode)) {
    die('Invalid format');
}
```

---

## II. PHP Coding Standards

### A. File Organization

1. **Session First**: `session_start()` MUST be the first executable line
2. **Config Include**: Include config.php before any business logic
3. **Error Reporting**: Set at top of file for development
4. **Maximum Lines**: 500 lines per file (split if larger)

```php
<?php
// 1. Error reporting (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Session
session_start();

// 3. Config
require_once __DIR__ . '/../config.php';

// 4. Validation
// ...

// 5. Business Logic
// ...

// 6. Output
// ...
```

### B. Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Variables | snake_case | `$nama_siswa`, `$tanggal_lahir` |
| Constants | UPPER_SNAKE | `MAX_ATTEMPTS`, `STATUS_AKTIF` |
| Functions | snake_case | `get_data_siswa()`, `send_notification()` |
| Classes | PascalCase | `SiswaModel`, `WaTemplate` |
| Database tables | snake_case (plural) | `siswa`, `users`, `wa_templates` |
| Database columns | snake_case | `id_pendaftaran`, `tgl_daftar` |
| JavaScript | camelCase | `getStudentData()`, `validateForm()` |
| CSS classes | kebab-case | `.btn-submit`, `.form-group` |

### C. Comment Standards

```php
<?php
/**
 * Get student data by registration ID
 * 
 * @param string $idDaftar Registration ID (format: XX0000)
 * @return array|null Student data or null if not found
 * @throws PDOException When database error occurs
 */
function getStudentByIdDaftar(string $idDaftar): ?array {
    // Single line comment for inline explanation
    
    /* Multi-line for complex logic
     * that needs more explanation
     */
}
```

### D. Error Handling

```php
// ✅ CORRECT - Exception based
try {
    $stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        throw new RuntimeException("Student not found");
    }
    
    return $student;
} catch (RuntimeException $e) {
    error_log($e->getMessage());
    return null;
}

// ❌ WRONG - Silent failure
$result = mysqli_query($conn, $query);
if (!$result) {
    // Do nothing
}
```

---

## III. Database Standards

### A. Query Style

```php
// ✅ Preferred - Prepared statements with named parameters
$stmt = $pdo->prepare("
    SELECT s.id, s.nama, s.jurusan, g.nama as gelombang
    FROM siswa s
    JOIN gelombang g ON s.gelombang_id = g.id
    WHERE s.status_siswa = :status
    ORDER BY s.tgl_daftar DESC
");
$stmt->execute(['status' => 'aktif']);
$siswaList = $stmt->fetchAll();

// Alternative - Positional parameters
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id_pendaftaran = ?");
$stmt->execute([$idDaftar]);
```

### B. Index Usage

**RULE:** Add indexes for columns used in WHERE clauses.

```sql
-- ✅ For filtering
CREATE INDEX idx_status ON siswa(status_siswa);
CREATE INDEX idx_jurusan ON siswa(jurusan);

-- ✅ For lookups
CREATE UNIQUE INDEX idx_id_pendaftaran ON siswa(id_pendaftaran);

-- ✅ Composite indexes for common queries
CREATE INDEX idx_jurusan_status ON siswa(jurusan, status_siswa);
```

### C. Migration Files

```sql
-- migrations/003_add_new_field.sql

-- Description: Add asal_sekolah field to siswa table
-- Author: Admin
-- Date: 2026-06-17

BEGIN;

ALTER TABLE siswa 
ADD COLUMN asal_sekolah VARCHAR(100) AFTER no_hp;

-- Add index for school lookup
CREATE INDEX idx_asal_sekolah ON siswa(asal_sekolah);

COMMIT;
```

---

## IV. Frontend Standards

### A. HTML Structure

```html
<!-- ✅ Semantic HTML -->
<form action="/submit.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    
    <div class="form-group">
        <label for="nama">Nama Lengkap</label>
        <input type="text" id="nama" name="nama" required
               value="<?= htmlspecialchars($nama ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    
    <button type="submit" class="btn btn-primary">Simpan</button>
</form>
```

### B. JavaScript Best Practices

```javascript
// ✅ CORRECT - ES6+ with validation
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registrationForm');
    
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) throw new Error('Network error');
                
                const result = await response.json();
                if (result.success) {
                    window.location.href = result.redirect;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            }
        });
    }
});

// ❌ WRONG - Inline event handlers
<button onclick="submitForm()">Submit</button>
```

### C. CSS Organization

```css
/* CSS file structure */

/* 1. Variables */
:root {
    --primary-color: #007bff;
    --font-family: 'Segoe UI', sans-serif;
}

/* 2. Reset */
*, *::before, *::after {
    box-sizing: border-box;
}

/* 3. Layout */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* 4. Components */
.btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
}
.btn-primary {
    background: var(--primary-color);
    color: white;
}

/* 5. Utilities */
.text-center { text-align: center; }
.mt-1 { margin-top: 0.5rem; }
```

---

## V. Git Workflow

### A. Branch Naming

```
feat/nama-fitur          # New features
fix/nama-bug             # Bug fixes
refactor/nama            # Code refactoring
docs/nama                # Documentation
security/nama            # Security changes
```

### B. Commit Messages (Conventional Commits)

```
feat(database): add new field for student origin school

Adds asal_sekolah column to siswa table for better tracking
of student backgrounds.

BREAKING CHANGE: Migration required

Closes #123
```

**Format:**
```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `refactor`: Code refactoring
- `docs`: Documentation
- `style`: Formatting
- `perf`: Performance
- `security`: Security fix
- `test`: Adding tests
- `chore`: Maintenance

### C. Pull Request Checklist

- [ ] Code follows coding standards
- [ ] Security issues addressed
- [ ] Database migrations included (if needed)
- [ ] Tested on development environment
- [ ] Documentation updated
- [ ] No console errors
- [ ] Mobile responsive

---

## VI. Prohibited Practices

### NEVER DO:

1. ❌ String concatenation in SQL queries
2. ❌ `eval()` or `exec()`
3. ❌ `register_globals` or `magic_quotes`
4. ❌ Display errors in production (`display_errors = On`)
5. ❌ Store passwords in plain text
6. ❌ Trust `$_SERVER['REMOTE_ADDR']` without validation
7. ❌ Include files based on user input without validation
8. ❌ Use `$_GET` for sensitive operations
9. ❌ Hardcode credentials in source code
10. ❌ Output user input without escaping

### ALWAYS DO:

1. ✅ Use prepared statements
2. ✅ Escape output with `htmlspecialchars()`
3. ✅ Validate and sanitize all input
4. ✅ Use CSRF tokens on forms
5. ✅ Hash passwords with bcrypt
6. ✅ Use environment variables for secrets
7. ✅ Log errors, don't display them in production
8. ✅ Use HTTPS everywhere
9. ✅ Keep dependencies updated
10. ✅ Write readable, commented code

---

## VII. Code Review Checklist

When reviewing code, check:

### Security
- [ ] No SQL injection vulnerabilities
- [ ] XSS prevention in place
- [ ] CSRF tokens on all POST forms
- [ ] Passwords properly hashed
- [ ] Input validation present

### Code Quality
- [ ] Follows naming conventions
- [ ] Functions are reasonably sized
- [ ] Comments explain "why", not "what"
- [ ] No commented-out code
- [ ] No TODO comments without issue tracking

### Performance
- [ ] Database queries are optimized
- [ ] Indexes used appropriately
- [ ] No N+1 query problems
- [ ] Assets properly loaded

### Maintainability
- [ ] Code is DRY (Don't Repeat Yourself)
- [ ] Functions have single responsibility
- [ ] Error handling is consistent
- [ ] Configuration is externalized

---

## VIII. File Templates

### PHP File Header

```php
<?php
/**
 * File: nama_file.php
 * Description: Brief description of what this file does
 * 
 * @package SPMB
 * @author Author Name
 * @version 1.0.0
 * @date 2026-06-17
 * 
 * @see related_file.php For more context
 */

// Error reporting (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session
session_start();

// Config
require_once __DIR__ . '/../config.php';

// ... rest of code
```

### Migration File Template

```sql
-- migrations/XXX_description.sql

-- Description: What this migration does
-- Author: Your Name
-- Date: YYYY-MM-DD

-- @UNDO: Description of how to rollback

BEGIN;

-- Your migration SQL here

COMMIT;
```
