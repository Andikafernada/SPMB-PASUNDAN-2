# Security Audit Skill

## Overview
This skill guides security audits for the SPMB project code.

## When to Use

Use this skill when:
- Reviewing code before deployment
- Fixing security issues
- Adding new form handlers
- Implementing authentication
- Processing user uploads

## Audit Checklist

### 1. SQL Injection

**Check all database queries:**

```php
// ❌ VULNERABLE
$query = "SELECT * FROM siswa WHERE id = " . $_GET['id'];

// ✅ SECURE
$stmt = $conn->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->bind_param("i", $_GET['id']);
```

**Grep patterns to search:**
```bash
grep -n "mysqli_query\|mysql_query" /var/www/html/*.php
grep -n '"SELECT.*\$' /var/www/html/*.php
grep -n "'SELECT.*\$" /var/www/html/*.php
```

### 2. XSS Prevention

**Check all output:**

```php
// ❌ VULNERABLE
<?= $variable ?>
<?php echo $variable; ?>
<?= $_GET['param'] ?>

// ✅ SECURE
<?= htmlspecialchars($variable, ENT_QUOTES, 'UTF-8') ?>
```

**Grep patterns:**
```bash
grep -n "echo \$_" /var/www/html/views/*.php
grep -n "<?=" /var/www/html/views/*.php
```

### 3. CSRF Protection

**Check all POST forms:**

```php
// ✅ Form has token
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// ✅ Handler validates
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Invalid CSRF token');
}
```

**Grep patterns:**
```bash
grep -n "method=\"POST\"" /var/www/html/views/*.php
grep -n "csrf_token" /var/www/html/views/*.php
```

### 4. Input Validation

**Check user input handling:**

```php
// ❌ VULNERABLE - No validation
$name = $_POST['nama'];

// ✅ SECURE - With validation
$name = filter_input(INPUT_POST, 'nama', FILTER_SANITIZE_STRING);
if (!$name || strlen($name) < 2) {
    die('Invalid name');
}
```

### 5. Password Security

**Check authentication:**

```php
// ❌ WRONG - Plain text
if ($_POST['password'] === $storedPassword) {}

// ✅ CORRECT - Hash verification
if (password_verify($_POST['password'], $storedHash)) {}

// ❌ WRONG - MD5/SHA1
$hash = md5($_POST['password']);

// ✅ CORRECT - bcrypt
$hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
```

### 6. File Upload Security

**Check upload handlers:**

```php
// ✅ Secure upload
$allowedTypes = ['image/jpeg', 'image/png'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    die('Invalid file type');
}

$filename = basename($file['name']);
$uploadPath = UPLOAD_DIR . '/' . uniqid() . '_' . $filename;
```

## Common Vulnerability Patterns

### SQL Injection Checklist
- [ ] No string concatenation in queries
- [ ] All queries use prepared statements
- [ ] User input goes through bind_param()
- [ ] No quotes around bound parameters

### XSS Checklist
- [ ] All echo/print uses htmlspecialchars()
- [ ] User content rendered with textContent
- [ ] No innerHTML with user data
- [ ] Content-Type header set to UTF-8

### CSRF Checklist
- [ ] All POST forms have CSRF token
- [ ] Token validated before processing
- [ ] Token is random and unpredictable
- [ ] Token regenerated on login

## Audit Report Template

```markdown
## Security Audit Report

**Date:** YYYY-MM-DD
**Auditor:** [Name]
**Files Audited:** [List]

### Issues Found

| Severity | Type | File | Line | Description | Fix |
|----------|------|------|------|-------------|-----|
| HIGH | SQL Injection | example.php | 42 | String concat | Prepared statement |

### Vulnerabilities Fixed
- [ ] SQL injection in prosess_crud.php
- [ ] XSS in edit.php

### Recommendations
1. Implement input sanitization library
2. Add CSP headers
3. ...

### Overall Score: X/10
**Status:** PASS / FAIL
```

## Quick Audit Commands

```bash
# Check for SQL injection patterns
grep -rn "mysqli_query\|\.=\"\|CONCAT(" --include="*.php" /var/www/html/

# Check for XSS patterns  
grep -rn "echo \$_GET\|echo \$_POST\|<?= \$" --include="*.php" /var/www/html/

# Check for missing CSRF
grep -rn "method=\"POST\"" --include="*.php" /var/www/html/views/

# Check for password issues
grep -rn "md5\|sha1\|crypt" --include="*.php" /var/www/html/
```
