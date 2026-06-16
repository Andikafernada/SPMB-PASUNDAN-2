# Panduan Update N8N Workflow - WhatsApp Template System

## Masalah
Pesan WhatsApp yang diterima siswa mengandung `undefined` karena N8N membuat pesan sendiri dari `raw_data` tanpa placeholder replacement.

## Solusi
Gunakan field `message` yang sudah di-render dari PHP (Opsi 1 - Recommended)

---

## Langkah-Langkah Update N8N

### 1. Login ke N8N
```
URL: http://172.16.0.180:5678
```

### 2. Buka Workflow WhatsApp
- Cari workflow yang terkait dengan "SPMB" atau "WhatsApp" atau "WA"
- Klik untuk edit

### 3. Identifikasi Node Pengirim WhatsApp
- Cari node **"WhatsApp"** atau **"WaApi"** atau **"Chat API"**
- Node ini biasanya ada di akhir workflow

### 4. Update Expression untuk Pesan

**SEBELUM (Salah):**
```
{{ $json.raw_data.nama }}
{{ $json.raw_data.id_daftar }}
dst...
```

**SESUDAH (Benar):**
```
{{ $json.message }}
```

### 5. Contoh Langkah-Langkah Detail

#### A. Cari node WhatsApp Sender
```
┌─────────────────────────────────────┐
│  Webhook (Trigger)                  │
│         │                           │
│         ▼                           │
│  [Process/Data]                     │
│         │                           │
│         ▼                           │
│  WhatsApp (Send Message) ◄── UBAH   │
│         │                           │
│         ▼                           │
│  [Response]                         │
└─────────────────────────────────────┘
```

#### B. Edit Field Message di WhatsApp Node

1. **Double-click** pada node WhatsApp
2. Cari field **"Message"** atau **"Text"**
3. Ubah dari expression lama ke:
   ```
   {{ $json.message }}
   ```

#### C. Hapus/Pindahkan Logic Template Replacement

Jika ada **Code Node** atau node lain yang membuat pesan dari `raw_data`:
1. Hapus node tersebut, ATAU
2. Ubah agar output-nya tetap pakai `{{ $json.message }}`

---

## Payload yang Dikirim PHP (untuk referensi)

```json
{
  "wa": "6281234567890",
  "type": "acc",
  "kode": "ACC_PENDAFTARAN",
  "message": "Assalamualaikum BUDI SANTOSO! 🎉\n\nPendaftaran Anda di *SMK Pasundan 2* telah DISETUJUI.\n\n📋 *Detail Pendaftaran:*\n• ID Pendaftaran: *SPMB26-001*\n• Jurusan: *TEKNIK KENDARAAN RINGAN*\n• Asal Sekolah: SMP NEGERI 1 BANDUNG\n\nSilakan lakukan daftar ulang sesuai jadwal.\n\nTerdaftar oleh: Admin TU\nTanggal: 16/06/2026 03:28",
  "raw_data": {
    "nama": "BUDI SANTOSO",
    "id_daftar": "SPMB26-001",
    "sekolah": "SMP NEGERI 1 BANDUNG",
    "jurusan": "TEKNIK KENDARAAN RINGAN",
    "admin": "Admin TU",
    "tanggal": "16/06/2026 03:28"
  },
  "timestamp": "2026-06-16 03:28:59"
}
```

**N8N cukup kirim `{{ $json.message }}` ke WhatsApp - semua placeholder sudah di-replace oleh PHP.**

---

## Testing

### Test Manual:
1. Aktifkan workflow
2. Trigger dari PHP (ACC siswa)
3. Cek WhatsApp siswa - harusnya tidak ada `undefined`

### Test Payload:
```bash
# Test kirim manual ke N8N
curl -X POST "http://172.16.0.180:5678/webhook/b3849cc7-fa0e-4e4a-aca4-54cc49f5325f" \
  -H "Content-Type: application/json" \
  -d '{
    "wa": "6281234567890",
    "message": "TEST: Assalamualaikum BUDI SANTOSO! Pendaftaran SPMB26-001 berhasil.",
    "raw_data": {"nama": "BUDI SANTOSO"},
    "type": "acc",
    "kode": "ACC_PENDAFTARAN"
  }'
```

---

## Checklist After Update

- [ ] Login N8N ✓
- [ ] Buka workflow WA ✓
- [ ] Edit node WhatsApp ✓
- [ ] Ubah ke `{{ $json.message }}` ✓
- [ ] Hapus/disable logic template lama ✓
- [ ] Save & Activate workflow ✓
- [ ] Test dengan ACC siswa ✓
- [ ] Verifikasi pesan di WhatsApp (tanpa undefined) ✓

---

## Rollback Plan (Jika Gagal)

Jika ada masalah, revert ke:
```
{{ "Pesan: " + $json.raw_data.nama + " - " + $json.raw_data.id_daftar }}
```

Atau disable node sementara dan test dengan PHP langsung.
