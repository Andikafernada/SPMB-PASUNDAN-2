# TPA System Skill

## Overview
This skill guides development and maintenance of the TPA (Tes Potensi Akademik) system.

## When to Use

Use this skill when:
- Modifying TPA logic
- Adding new question categories
- Fixing timer or session issues
- Updating certificate/card generation
- Adding new heroes

## System Overview

### Components

| Component | File | Purpose |
|-----------|------|---------|
| Login | `views/tpa/login.php` | Student authentication |
| Hero Select | `views/tpa/hero_select.php` | Choose exam avatar |
| Exam | `views/tpa/index.php` | Main exam interface |
| Results | `views/tpa/hasil.php` | Score display |
| Card | `views/tpa/card.php` | Achievement card |
| Certificate | `views/tpa/sertifikat.php` | PDF certificate |

### Question Categories

| Category | Count | Time | Hero Bonus |
|----------|-------|------|------------|
| Verbal | 15 | 15 min | PENSHE +10% |
| Numerik | 15 | 15 min | BILANGO +10% |
| Logika | 10 | 10 min | LOGIX +10% |

### Hero Stats

| Hero | Verbal | Numerik | Logika |
|------|--------|---------|--------|
| PENSHE | 110 | 100 | 100 |
| BILANGO | 100 | 110 | 100 |
| LOGIX | 100 | 100 | 110 |
| EQUILIS | 105 | 105 | 105 |
| TITAN | 100 | 100 | 100 |

## Adding New Questions

### Database Schema

```sql
CREATE TABLE tpa_soal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori ENUM('verbal', 'numerik', 'logika') NOT NULL,
    soal TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    jawaban ENUM('A', 'B', 'C', 'D') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Add Question

```php
$stmt = $conn->prepare("
    INSERT INTO tpa_soal (kategori, soal, option_a, option_b, option_c, option_d, jawaban)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("sssssss", 
    $kategori, 
    $soal, 
    $optionA, 
    $optionB, 
    $optionC, 
    $optionD, 
    $jawaban
);
$stmt->execute();
```

### Random Question Selection

```php
function getRandomQuestions($category, $count, $excludeIds = []) {
    global $conn;
    
    $placeholders = str_repeat('?,', count($excludeIds));
    $params = array_merge([$category, $count], $excludeIds);
    $types = 'si' . str_repeat('i', count($excludeIds));
    
    $sql = "
        SELECT * FROM tpa_soal 
        WHERE kategori = ? 
        AND id NOT IN (" . ($placeholders ?: 'NULL') . ")
        ORDER BY RAND() 
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
```

## Timer Implementation

### Server-Side (PHP)

```php
// Start exam
if (!isset($_SESSION['tpa_start_time'])) {
    $_SESSION['tpa_start_time'] = time();
    $_SESSION['tpa_hero'] = $hero;
}

// Check time remaining
$startTime = $_SESSION['tpa_start_time'];
$duration = 45 * 60; // 45 minutes
$elapsed = time() - $startTime;

if ($elapsed >= $duration) {
    // Auto-submit
    submitExam($_SESSION['student_id']);
    header('Location: hasil.php');
    exit;
}

$remaining = $duration - $elapsed;
```

### Client-Side (JavaScript)

```javascript
const Timer = {
    remaining: <?= $remaining ?>,
    
    start() {
        setInterval(() => this.tick(), 1000);
    },
    
    tick() {
        this.remaining--;
        this.updateDisplay();
        
        if (this.remaining <= 0) {
            document.getElementById('examForm').submit();
        }
    },
    
    updateDisplay() {
        const minutes = Math.floor(this.remaining / 60);
        const seconds = this.remaining % 60;
        document.getElementById('timer').textContent = 
            `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }
};

Timer.start();
```

## Anti-Cheat Measures

### No Back Button

```javascript
// Prevent back
history.pushState(null, '', location.href);
window.addEventListener('popstate', () => {
    history.pushState(null, '', location.href);
});

// Disable right-click on sensitive pages
document.addEventListener('contextmenu', e => e.preventDefault());
```

### Session Lock

```php
// Check if already taking exam
if (isset($_SESSION['tpa_start_time']) && !isset($_GET['resume'])) {
    $elapsed = time() - $_SESSION['tpa_start_time'];
    if ($elapsed < 45 * 60) {
        header('Location: index.php?resume=1');
        exit;
    }
}
```

## Score Calculation

```php
function calculateTpaScore($studentId) {
    global $conn;
    
    // Get answers
    $stmt = $conn->prepare("
        SELECT j.*, s.kategori, s.jawaban as jawaban_benar
        FROM tpa_jawaban j
        JOIN tpa_soal s ON j.id_soal = s.id
        WHERE j.id_siswa = ?
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $answers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate by category
    $score = [
        'verbal' => ['benar' => 0, 'total' => 15],
        'numerik' => ['benar' => 0, 'total' => 15],
        'logika' => ['benar' => 0, 'total' => 10]
    ];
    
    foreach ($answers as $answer) {
        if ($answer['jawaban'] === $answer['jawaban_benar']) {
            $score[$answer['kategori']]['benar']++;
        }
    }
    
    // Apply hero bonus
    $hero = $_SESSION['tpa_hero'] ?? 'TITAN';
    $bonus = getHeroBonus($hero);
    
    foreach ($score as $cat => &$data) {
        $data['skor'] = round($data['benar'] / $data['total'] * 100 * $bonus[$cat]);
    }
    
    return $score;
}

function getHeroBonus($hero) {
    $bonuses = [
        'PENSHE' => ['verbal' => 1.1, 'numerik' => 1, 'logika' => 1],
        'BILANGO' => ['verbal' => 1, 'numerik' => 1.1, 'logika' => 1],
        'LOGIX' => ['verbal' => 1, 'numerik' => 1, 'logika' => 1.1],
        'EQUILIS' => ['verbal' => 1.05, 'numerik' => 1.05, 'logika' => 1.05],
        'TITAN' => ['verbal' => 1, 'numerik' => 1, 'logika' => 1]
    ];
    
    return $bonuses[$hero] ?? $bonuses['TITAN'];
}
```

## Card Generation

```php
// Using GD library or canvas
function generateAchievementCard($student, $score, $hero) {
    $card = imagecreatefrompng(__DIR__ . '/../assets/tpa/card-template.png');
    
    // Colors
    $white = imagecolorallocate($card, 255, 255, 255);
    $gold = imagecolorallocate($card, 255, 215, 0);
    
    // Hero image
    $heroImg = imagecreatefrompng(__DIR__ . '/../assets/tpa/heroes/' . strtolower($hero) . '.png');
    imagecopy($card, $heroImg, 50, 50, 0, 0, 100, 100);
    
    // Text
    imagestring($card, 5, 200, 100, $student['nama'], $white);
    imagestring($card, 4, 200, 130, "Total: {$score['total']}%", $gold);
    
    // Output
    header('Content-Type: image/png');
    imagepng($card);
    imagedestroy($card);
}
```

## Testing Checklist

- [ ] Login with valid ID
- [ ] Login with invalid ID
- [ ] Hero selection persists
- [ ] Timer counts down correctly
- [ ] Questions display properly
- [ ] Back button disabled
- [ ] Page refresh handled
- [ ] Auto-submit on timeout
- [ ] Score calculation correct
- [ ] Card generation works
- [ ] Certificate PDF generated
