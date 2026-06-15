<?php
/**
 * AI PROCESSOR - SPMB SMK Pasundan 2 Bandung
 * NOTE (2026-06-10): DeepSeek API integration DISABLED
 * AI matchmaker saat ini menggunakan logika rule-based (JavaScript)
 * Tidak ada endpoint yang memanggil DeepSeek API
 *
 * Jika ingin mengaktifkan AI di masa depan:
 * 1. Tambahkan DEEPSEEK_API_KEY di .env
 * 2. Aktifkan call di sini
 * 3. Update endpoint ini sesuai kebutuhan
 */
header('Content-Type: application/json');
echo json_encode([
    "jurusan" => "TKJ",
    "alasan" => "Fitur AI matchmaker saat ini menggunakan logika rule-based di sisi client (JavaScript). Tidak memerlukan endpoint server untuk proses ini."
]);
?>