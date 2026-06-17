# SQL Coding Rules

## Overview
These rules apply to all SQL queries and database operations in the SPMB project.

---

## 1. Prepared Statements (MANDATORY)

```php
// ✅ CORRECT
$stmt = $conn->prepare("SELECT * FROM siswa WHERE id_pendaftaran = ?");
$stmt->bind_param("s", $idDaftar);
$stmt->execute();

// ❌ WRONG
$query = "SELECT * FROM siswa WHERE id_pendaftaran = '$idDaftar'";
```

### Parameter Types

| Type | Code | Usage |
|------|------|-------|
| Integer | i | `bind_param("i", $id)` |
| String | s | `bind_param("s", $name)` |
| Double | d | `bind_param("d", $score)` |
| Blob | b | `bind_param("b", $data)` |

```php
// Multiple parameters
$stmt = $conn->prepare("
    SELECT * FROM siswa 
    WHERE id_pendaftaran = ? AND status_siswa = ?
");
$stmt->bind_param("ss", $idDaftar, $status);
```

---

## 2. Query Formatting

```sql
-- ✅ Readable format
SELECT 
    s.id,
    s.nama,
    s.jurusan,
    g.nama AS gelombang_nama
FROM siswa s
JOIN gelombang g ON s.gelombang_id = g.id
WHERE s.status_siswa = 'aktif'
ORDER BY s.tgl_daftar DESC
LIMIT 10;

-- ❌ Compact format
SELECT s.id,s.nama FROM siswa s WHERE s.id=?;
```

---

## 3. Index Strategy

### Always Index
- Primary keys (automatic)
- Foreign keys
- Columns in WHERE clauses
- Columns in JOIN conditions
- Columns in ORDER BY (if frequently used)

### Index Examples

```sql
-- Single column index
CREATE INDEX idx_status ON siswa(status_siswa);

-- Composite index (column order matters!)
CREATE INDEX idx_jurusan_status ON siswa(jurusan, status_siswa);

-- Unique index
CREATE UNIQUE INDEX idx_id_pendaftaran ON siswa(id_pendaftaran);
```

---

## 4. Table Naming

```sql
-- ✅ snake_case, plural
CREATE TABLE siswa ();
CREATE TABLE users ();
CREATE TABLE wa_templates ();

-- ❌ Don't use
CREATE TABLE student ();
CREATE TABLE User ();
CREATE TABLE waTemplate ();
```

---

## 5. Column Naming

```sql
-- ✅ snake_case
id_pendaftaran
tanggal_lahir
status_siswa

-- ✅ Boolean columns: is_ or status_
is_active
status_siswa

-- ✅ Timestamps: _at or _on
created_at
updated_at
deleted_at
```

---

## 6. Data Types

### Use Appropriate Types

```sql
-- ✅ Correct
status ENUM('aktif', 'nonaktif', 'pending')
gender ENUM('L', 'P')
phone VARCHAR(20)
postcode VARCHAR(10)

-- ❌ Overly permissive
status VARCHAR(255)
phone TEXT
postcode TEXT
```

### Common Types

| Data | Type | Notes |
|------|------|-------|
| ID | INT AUTO_INCREMENT | Primary key |
| UUID | CHAR(36) | For external references |
| Status | ENUM | Limited options |
| Name | VARCHAR(100) | Short text |
| Description | TEXT | Long text |
| Phone | VARCHAR(20) | Include + |
| Currency | DECIMAL(10,2) | Precise math |
| Boolean | TINYINT(1) | 0 or 1 |

---

## 7. JOINs

```sql
-- ✅ Use aliases for readability
SELECT 
    s.nama,
    g.nama AS gelombang
FROM siswa s
LEFT JOIN gelombang g ON s.gelombang_id = g.id;

-- ✅ Explicit JOINs over implicit
-- ❌ SELECT * FROM siswa, gelombang WHERE ...
-- ✅ SELECT * FROM siswa JOIN gelombang ON ...
```

---

## 8. Transactions

```php
// For multi-table operations
$conn->begin_transaction();

try {
    // Insert student
    $stmt1 = $conn->prepare("INSERT INTO siswa (nama, ...) VALUES (?, ...)");
    $stmt1->bind_param("s", $nama);
    $stmt1->execute();
    
    $studentId = $conn->insert_id;
    
    // Create audit log
    $stmt2 = $conn->prepare("INSERT INTO audit_log (...) VALUES (?, ...)");
    $stmt2->bind_param("iss", $userId, $action, $details);
    $stmt2->execute();
    
    $conn->commit();
    
} catch (Exception $e) {
    $conn->rollback();
    throw $e;
}
```

---

## 9. Migration Files

```sql
-- migrations/003_add_field.sql

-- Description: Add asal_sekolah field to siswa table
-- Author: Admin
-- Date: 2026-06-17
-- @UNDO: ALTER TABLE siswa DROP COLUMN asal_sekolah

BEGIN;

ALTER TABLE siswa 
ADD COLUMN asal_sekolah VARCHAR(100) AFTER no_hp;

CREATE INDEX idx_asal_sekolah ON siswa(asal_sekolah);

COMMIT;
```

### Migration Rules
1. One logical change per migration
2. Include rollback instructions
3. Test rollback before committing
4. Never modify existing migrations

---

## 10. Query Optimization

### DO
- SELECT specific columns, not *
- Use LIMIT for pagination
- Index WHERE columns
- Use EXPLAIN to analyze queries
- Cache results when appropriate

### DON'T
- SELECT * in production code
- Use LIKE '%search%' on large tables
- Nest subqueries when JOINs work
- Ignore query performance

```sql
-- ✅ Pagination
SELECT * FROM siswa 
ORDER BY tgl_daftar DESC 
LIMIT 20 OFFSET 40;

-- ✅ Use EXPLAIN
EXPLAIN SELECT * FROM siswa WHERE status_siswa = 'aktif';
```
