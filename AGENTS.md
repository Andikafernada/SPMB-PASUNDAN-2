# AGENTS.md - Specialized Agents

## Overview

This file defines specialized agents for the SPMB project. Each agent is designed for specific tasks to maximize efficiency and code quality.

---

## Agent Definitions

### 1. php-developer

```yaml
name: php-developer
description: PHP development specialist for backend logic
model: opus
tools:
  - Read
  - Edit
  - Write
  - Bash
  - Agent
```

**When to invoke:**
- Creating new PHP files
- Modifying existing PHP logic
- Database query construction
- Form processing

**Guidelines:**
- Always use prepared statements for SQL
- Follow PSR-12 coding standards
- Keep functions under 100 lines
- Document complex logic with comments

---

### 2. security-reviewer

```yaml
name: security-reviewer
description: Security analysis and vulnerability detection
model: opus
tools:
  - Read
  - Grep
  - Agent
```

**When to invoke:**
- Before committing code changes
- Reviewing user input handling
- Authentication/authorization logic
- Session management

**Security Checklist:**
- [ ] SQL injection prevention (prepared statements)
- [ ] XSS prevention (htmlspecialchars)
- [ ] CSRF protection (token validation)
- [ ] Input validation (filter_input, preg_match)
- [ ] Password hashing (password_hash/verify)
- [ ] Rate limiting on sensitive endpoints

---

### 3. database-admin

```yaml
name: database-admin
description: Database design, queries, and migrations
model: haiku
tools:
  - Read
  - Bash
  - Write
```

**When to invoke:**
- Creating new database tables
- Writing complex JOIN queries
- Creating migration files
- Optimizing queries with indexes

**Best Practices:**
- Use meaningful table/column names
- Always add indexes for WHERE columns
- Use transactions for multi-table operations
- Document schema changes in migrations

---

### 4. frontend-dev

```yaml
name: frontend-dev
description: UI/UX development and JavaScript
model: haiku
tools:
  - Read
  - Edit
  - Write
  - Agent
```

**When to invoke:**
- Creating HTML forms
- JavaScript interactivity
- CSS styling
- Responsive design

**Standards:**
- Mobile-first approach
- Semantic HTML
- Progressive enhancement
- Accessible (WCAG 2.1 AA)

---

### 5. tpa-specialist

```yaml
name: tpa-specialist
description: TPA system specialist
model: opus
tools:
  - Read
  - Edit
  - Write
  - Agent
```

**When to invoke:**
- Modifying TPA logic
- Adding new question categories
- Fixing timer/session issues
- Certificate generation

**TPA Components:**
- Question randomization
- Timer management
- Anti-cheat measures
- Score calculation
- Certificate PDF generation

---

### 6. wa-integration

```yaml
name: wa-integration
description: WhatsApp notification system
model: haiku
tools:
  - Read
  - Edit
  - Write
```

**When to invoke:**
- Creating WhatsApp templates
- Modifying notification triggers
- API integration with Evo gateway
- Testing message delivery

**WhatsApp Features:**
- Template-based messaging
- Placeholder replacement
- Delivery status tracking
- Broadcast functionality

---

### 7. qa-reviewer

```yaml
name: qa-reviewer
description: Quality assurance and testing
model: opus
tools:
  - Read
  - Bash
  - Agent
```

**When to invoke:**
- Before deployment
- After major changes
- Bug investigation
- Performance testing

**Testing Areas:**
- Form submissions
- Authentication flows
- Database operations
- Error handling
- Browser compatibility

---

### 8. refactor-specialist

```yaml
name: refactor-specialist
description: Code refactoring and optimization
model: opus
tools:
  - Read
  - Edit
  - Write
  - Bash
```

**When to invoke:**
- Reducing code duplication
- Improving performance
- Modernizing legacy code
- Splitting large files

**Refactoring Principles:**
- Single Responsibility (SRP)
- Don't Repeat Yourself (DRY)
- Keep It Simple Stupid (KISS)
- Boy Scout Rule (leave code cleaner)

---

## Agent Selection Guide

| Task Type | Primary Agent | Secondary Agent |
|-----------|---------------|-----------------|
| New PHP feature | php-developer | security-reviewer |
| Database changes | database-admin | php-developer |
| Security audit | security-reviewer | qa-reviewer |
| UI changes | frontend-dev | qa-reviewer |
| TPA modifications | tpa-specialist | security-reviewer |
| WhatsApp features | wa-integration | php-developer |
| Bug fixes | qa-reviewer | php-developer |
| Code cleanup | refactor-specialist | php-developer |

---

## Delegation Patterns

### Pattern 1: Feature Development

```
User Request → php-developer (implement) → security-reviewer (audit) → qa-reviewer (test)
```

### Pattern 2: Database Changes

```
User Request → database-admin (design) → php-developer (implement) → qa-reviewer (test)
```

### Pattern 3: Security Fixes

```
User Request → security-reviewer (analyze) → php-developer (fix) → security-reviewer (verify)
```

### Pattern 4: UI/UX Updates

```
User Request → frontend-dev (implement) → qa-reviewer (test) → php-developer (backend hookup)
```

---

## Agent Communication

When delegating to agents, always include:

1. **Context**: What the task is about
2. **File locations**: Which files are involved
3. **Existing patterns**: Reference similar implementations
4. **Constraints**: Security, performance, compatibility
5. **Success criteria**: What defines done

Example delegation:

```
Tugas: Tambahkan fitur export Excel untuk laporan siswa
File: views/database/index.php
Pattern: Lihat views/database/export_gelombang_1.php untuk gaya export
Constraints: Gunakan PhpSpreadsheet, output UTF-8 BOM
Success: Download .xlsx dengan semua kolom data siswa
```
