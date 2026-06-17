# SOUL.md - Project Identity

## Core Identity

**SPMB SMK Pasundan 2 Bandung** adalah Sistem Penerimaan Murid Baru berbasis web yang dirancang untuk streamline proses pendaftaran siswa baru di SMK Pasundan 2 Bandung.

**Tagline:** "Menerima Murid Baru dengan Teknologi, Melayani dengan Hati"

---

## Mission

Menyediakan sistem informasi yang:
1. **Mudah** - Proses pendaftaran yang intuitif untuk calon siswa dan orang tua
2. **Aman** - Keamanan data siswa yang terjamin dengan standar industri
3. **Efisien** - Automasi proses administrasi yang mengurangi beban kerja staff
4. **Transparan** - Status pendaftaran yang bisa dipantau secara real-time

---

## Values

### 1. Integritas Data
> "Data siswa adalah tanggung jawab kita."

- Pastikan semua input divalidasi dengan benar
- Jangan pernah mengubah data tanpa alasan yang valid
- Jaga privasi informasi pribadi
- Logging semua perubahan penting untuk audit trail

### 2. Keamanan Pertama
> "Satu celah keamanan bisa merusak kepercayaan."

- Default: secure (deny by default)
- Multi-layer defense
- Defense in depth
- Regular security audits

### 3. User Experience
> "Teknologi harus membantu, bukan menghambat."

- Simple dan intuitif
- Mobile-friendly
- Clear error messages
- Progress indicators
- Accessible untuk semua kalangan

### 4. Kecepatan Respons
> "Waktu adalah nilai."

- Response time < 200ms untuk API calls
- Optimized database queries
- Lazy loading untuk komponen berat
- Caching strategy yang tepat

### 5. Kolaborasi
> "Bersama kita membangun."

- Dokumentasi yang lengkap
- Code review sebelum merge
- Knowledge sharing antar developer
- Transparansi dalam development

---

## Design Principles

### For Users (Calon Siswa & Orang Tua)

1. **Simplicity First**
   - Pendaftaran dalam 5 langkah
   - Progress bar yang jelas
   - Satu halaman fokus per tugas

2. **Helpful Feedback**
   - Notifikasi status via WhatsApp
   - Clear error messages in Bahasa Indonesia
   - FAQ untuk pertanyaan umum

3. **Trust Building**
   - Transparansi biaya
   - Informasi jurusan yang lengkap
   - Data statistik penerimaan

### For Admin (Tata Usaha & Committee)

1. **Efficiency**
   - Bulk operations
   - Quick search & filters
   - Dashboard dengan KPI penting

2. **Control**
   - Granular permission system
   - Complete audit trail
   - Data export flexibility

3. **Reliability**
   - Graceful error handling
   - Data backup automation
   - Recovery procedures documented

---

## Technical Philosophy

### PHP Development

```php
// We believe in:
// 1. Readability over cleverness
// 2. Explicit over implicit
// 3. Convention over configuration
// 4. Composition over inheritance
```

**Core tenets:**
- Prepared statements ALWAYS
- Type hints where possible
- Return values that make sense
- Error handling that's actionable

### Database Design

```
"We design for the data we have, not the data we might have."
- Use appropriate data types
- Index what you search
- Normalize until it hurts, denormalize until it works
```

### Frontend

```
"Progressive enhancement, not graceful degradation."
- Semantic HTML first
- Accessible by default
- Mobile-first approach
- Performance as feature
```

---

## Communication Style

### Bahasa Indonesia (Primary)

Untuk user-facing messages, gunakan:
- Bahasa Indonesia yang baik dan benar
- Tidak terlalu formal, tidak terlalu kasual
- Error messages yang actionable
- Confirmations yang reassuring

**Examples:**

```php
// ✅ GOOD - User friendly
$SUKSES = "Pendaftaran berhasil! Silakan lakukan pembayaran dan upload bukti.";

// ❌ BAD - Too technical
$ERROR = "Error: SQLSTATE[23000] Integrity constraint violation";

// ✅ GOOD - Clear confirmation
$CONFIRM = "Data Anda berhasil disimpan. ID Pendaftaran: " . $idDaftar;
```

### English (For Code Comments)

```php
// Use English for code comments
// Describe WHY, not WHAT

// Calculate total score from all categories
// Cache result for 1 hour to reduce DB load
```

---

## Risk Management

### Known Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data loss | Critical | Daily backups, point-in-time recovery |
| Security breach | Critical | WAF, regular audits, intrusion detection |
| Server overload | High | Load balancing, caching, rate limiting |
| Data inconsistency | Medium | Transactions, validation layers |
| User error | Low | Confirm dialogs, undo options |

### Security Posture

**Defend at all layers:**
1. Network - Firewall, WAF
2. Application - Input validation, CSRF, XSS prevention
3. Database - Prepared statements, least privilege
4. Session - Secure cookies, regenerate IDs
5. Infrastructure - Encryption at rest, HTTPS everywhere

---

## Success Metrics

### Performance

| Metric | Target |
|--------|--------|
| Page load time | < 2 seconds |
| API response time | < 200ms |
| Database query | < 50ms |
| File upload | < 5 seconds |

### Security

| Metric | Target |
|--------|--------|
| SQL injection vulnerabilities | 0 |
| XSS vulnerabilities | 0 |
| Failed login attempts blocked | 100% |
| CSRF tokens validated | 100% |

### Usability

| Metric | Target |
|--------|--------|
| Registration completion rate | > 85% |
| User satisfaction score | > 4.5/5 |
| Support tickets per registration | < 5% |

---

## Team & Contributors

### Current Maintainers

- **Project Lead:** [Admin Team]
- **Backend:** PHP developers
- **Frontend:** UI/UX team
- **Database:** DBA team

### Communication Channels

- GitHub Issues: Bug reports & feature requests
- Internal Documentation: /docs/
- Code Reviews: Pull request reviews

---

## Principles Summary

```
┌─────────────────────────────────────────────────────────┐
│  SPMB SMK Pasundan 2 Bandung - Core Values             │
├─────────────────────────────────────────────────────────┤
│  🔒 Security First     - Never compromise on safety     │
│  📊 Data Integrity     - Accuracy is non-negotiable     │
│  👥 User Centric       - Simple, helpful, accessible    │
│  ⚡ Performance        - Fast, responsive, reliable     │
│  📝 Documentation      - Clear, complete, current       │
└─────────────────────────────────────────────────────────┘
```

---

## Version

- **Version:** 1.0.0
- **Last Updated:** 2026-06-17
- **Changelog:** See git log
