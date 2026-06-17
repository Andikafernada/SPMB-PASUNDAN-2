# Database Migration Skill

## Overview
This skill guides database migrations for the SPMB project.

## When to Use

Use this skill when:
- Adding new tables
- Modifying existing tables
- Adding columns or indexes
- Creating or modifying stored procedures
- Any schema changes

## Migration Workflow

### 1. Create Migration File

```bash
# File naming: XXX_description.sql
# Example: 003_add_asal_sekolah.sql
```

### 2. Migration Template

```sql
-- migrations/003_add_asal_sekolah.sql

-- Description: Add asal_sekolah field to siswa table
-- Author: Admin
-- Date: 2026-06-17
-- @UNDO: ALTER TABLE siswa DROP COLUMN asal_sekolah;

BEGIN;

-- Add the column
ALTER TABLE siswa 
ADD COLUMN asal_sekolah VARCHAR(100) 
NULL 
AFTER no_hp
COMMENT 'Asal sekolah sebelumnya';

-- Add index for search
CREATE INDEX idx_asal_sekolah ON siswa(asal_sekolah);

COMMIT;
```

### 3. Execute Migration

```bash
mysql -u root -p db_sekolah < migrations/003_add_asal_sekolah.sql
```

### 4. Verify Migration

```sql
-- Check column exists
DESCRIBE siswa asal_sekolah;

-- Check index exists
SHOW INDEX FROM siswa WHERE Key_name = 'idx_asal_sekolah';
```

### 5. Rollback if Needed

```sql
-- From @UNDO comment
ALTER TABLE siswa DROP COLUMN asal_sekolah;
```

## Common Operations

### Add Column

```sql
BEGIN;

ALTER TABLE siswa 
ADD COLUMN new_column VARCHAR(100) 
AFTER existing_column;

CREATE INDEX idx_new_column ON siswa(new_column);

COMMIT;
```

### Rename Column

```sql
BEGIN;

ALTER TABLE siswa 
CHANGE COLUMN old_name new_name VARCHAR(50);

COMMIT;
```

### Add Index

```sql
-- Single column
CREATE INDEX idx_status ON siswa(status_siswa);

-- Composite (order matters!)
CREATE INDEX idx_jurusan_status ON siswa(jurusan, status_siswa);

-- Unique
CREATE UNIQUE INDEX idx_id_pendaftaran ON siswa(id_pendaftaran);
```

### Add Foreign Key

```sql
BEGIN;

ALTER TABLE siswa 
ADD CONSTRAINT fk_gelombang 
FOREIGN KEY (gelombang_id) 
REFERENCES gelombang(id) 
ON DELETE SET NULL;

COMMIT;
```

## Best Practices

### DO

1. **Always use BEGIN/COMMIT**
2. **Always include @UNDO comment**
3. **Test rollback before committing**
4. **Use meaningful column names**
5. **Add indexes for WHERE clauses**
6. **Document schema changes**

### DON'T

1. **Don't modify existing migrations**
2. **Don't drop columns without backup**
3. **Don't create migrations that can't be rolled back**
4. **Don't forget to commit**

## Existing Migrations

| File | Purpose | Status |
|------|---------|--------|
| wa_templates_init.sql | WhatsApp template system | ✅ |
| 002_database_optimization_indexes.sql | Performance indexes | ✅ |

## Migration Examples

### Example 1: Add Student Origin Field

```sql
-- migrations/003_add_asal_sekolah.sql

-- Description: Add asal_sekolah field to track student origins
-- Author: Admin
-- Date: 2026-06-17
-- @UNDO: ALTER TABLE siswa DROP COLUMN asal_sekolah;

BEGIN;

ALTER TABLE siswa 
ADD COLUMN asal_sekolah VARCHAR(100) 
NULL 
AFTER no_hp
COMMENT 'Asal sekolah sebelumnya';

CREATE INDEX idx_asal_sekolah ON siswa(asal_sekolah);

COMMIT;
```

### Example 2: Add Audit Columns

```sql
-- migrations/004_add_audit_columns.sql

-- Description: Add created_by and updated_by for audit trail
-- Author: Admin  
-- Date: 2026-06-17
-- @UNDO: ALTER TABLE siswa DROP COLUMN created_by, DROP COLUMN updated_by;

BEGIN;

ALTER TABLE siswa 
ADD COLUMN created_by INT 
NULL 
AFTER created_at,
ADD COLUMN updated_by INT 
NULL 
AFTER updated_at;

ALTER TABLE siswa 
ADD CONSTRAINT fk_created_by FOREIGN KEY (created_by) REFERENCES users(id),
ADD CONSTRAINT fk_updated_by FOREIGN KEY (updated_by) REFERENCES users(id);

COMMIT;
```

## Verification Commands

```bash
# Check table structure
mysql -u root -p db_sekolah -e "DESCRIBE siswa;"

# Check indexes
mysql -u root -p db_sekolah -e "SHOW INDEX FROM siswa;"

# Check foreign keys
mysql -u root -p db_sekolah -e "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'db_sekolah';"
```
