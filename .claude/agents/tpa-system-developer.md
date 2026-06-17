---
name: tpa-system-developer
description: TPA (Tes Potensi Akademik) system specialist
model: opus
tools:
  - Read
  - Edit
  - Write
  - Agent
---

# TPA System Developer Agent

## Purpose
Maintain and enhance the TPA (Tes Potensi Akademik) system with gamification features.

## System Architecture

### Components
1. **Login** - `views/tpa/login.php` - Auth via ID Pendaftaran
2. **Hero Selection** - `views/tpa/hero_select.php` - 5 hero options
3. **Exam** - `views/tpa/index.php` - 40 questions, 45 min timer
4. **Results** - `views/tpa/hasil.php` - Score calculation
5. **Card Generator** - `views/tpa/card.php` - Achievement image
6. **Certificate** - `views/tpa/sertifikat.php` - PDF certificate

### Question Structure
| Category | Count | Time |
|----------|-------|------|
| Verbal | 15 | ~15 min |
| Numerik | 15 | ~15 min |
| Logika | 10 | ~10 min |
| **Total** | **40** | **45 min** |

### Hero Options
1. PENSHE (The Strategist) - Verbal focus
2. BILANGO (The Calculator) - Numerik focus
3. LOGIX (The Analyzer) - Logika focus
4. EQUILIS (The Balanced) - Balanced stats
5. TITAN (The Champion) - All stats equal

## Implementation Details

### Question Selection
```php
// Random selection with category distribution
$verbal = getQuestionsByCategory('verbal', 15, $studentId);
$numerik = getQuestionsByCategory('numerik', 15, $studentId);
$logika = getQuestionsByCategory('logika', 10, $studentId);

$questions = array_merge($verbal, $numerik, $logika);
shuffle($questions);
```

### Timer Management
```php
// Client-side timer with server validation
$startTime = $_SESSION['tpa_start_time'];
$duration = 45 * 60; // 45 minutes in seconds
$elapsed = time() - $startTime;

if ($elapsed >= $duration) {
    // Auto-submit
    submitExam($studentId);
    header('Location: hasil.php');
    exit;
}
```

### Anti-Cheat Measures
- Session lock (can't re-enter)
- No back button (cache control)
- Time validation on server
- IP logging for suspicious activity

### Scoring Algorithm
```php
function calculateScore($answers, $questions) {
    $score = [
        'verbal' => ['correct' => 0, 'total' => 15],
        'numerik' => ['correct' => 0, 'total' => 15],
        'logika' => ['correct' => 0, 'total' => 10]
    ];
    
    foreach ($answers as $answer) {
        $question = getQuestion($answer['id_soal']);
        $category = $question['kategori'];
        
        if ($answer['jawaban'] === $question['jawaban']) {
            $score[$category]['correct']++;
        }
    }
    
    return $score;
}
```

## Common Tasks

### Add New Question
```php
// Insert into tpa_soal
INSERT INTO tpa_soal (kategori, soal, option_a, option_b, option_c, option_d, jawaban, created_at)
VALUES ('verbal', 'Pertanyaan...', 'A', 'B', 'C', 'D', 'A', NOW())
```

### Reset Student TPA
```php
// Delete answers and reset session
DELETE FROM tpa_jawaban WHERE id_siswa = ?;
UPDATE siswa SET tpa_selesai = 0 WHERE id = ?;
```

### Certificate Generation
```php
// Uses TCPDF or similar library
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'SERTIFIKAT', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 14);
$pdf->Cell(0, 10, $studentName, 0, 1, 'C');
// ... more content
$pdf->Output('sertifikat.pdf', 'I');
```

## Testing Checklist

- [ ] Timer accuracy
- [ ] Question randomization
- [ ] Score calculation
- [ ] Card image generation
- [ ] PDF certificate format
- [ ] Hero stats display
- [ ] Session persistence
- [ ] Mobile responsiveness
