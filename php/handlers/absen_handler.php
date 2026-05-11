<?php
session_start();
include_once __DIR__ . '/../../php/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Tidak terautentikasi']);
    exit;
}

$qr_token   = trim($_POST['qr_token'] ?? '');
$alasan_telat = trim($_POST['alasan_telat'] ?? '');

if (!$qr_token) {
    echo json_encode(['success' => false, 'message' => 'QR token tidak valid']);
    exit;
}

// Find karyawan by QR token
$stmt = $connect->prepare("
    SELECT k.*, j.nama as jabatan_nama FROM karyawan k
    LEFT JOIN jabatan j ON j.id = k.jabatan_id
    WHERE k.qr_code = ?
");
$stmt->bind_param("s", $qr_token);
$stmt->execute();
$karyawan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$karyawan) {
    echo json_encode(['success' => false, 'message' => 'QR Code tidak dikenali']);
    exit;
}

$karyawan_id = $karyawan['id'];
$today       = date('Y-m-d');
$now         = date('Y-m-d H:i:s');
$nowTime     = date('H:i:s');

// Get today's jadwal & shift
$stmt = $connect->prepare("
    SELECT jk.*, s.jam_masuk, s.jam_pulang, s.toleransi_menit, s.nama_shift, s.id as shift_id
    FROM jadwal_karyawan jk
    JOIN shifts s ON s.id = jk.shift_id
    WHERE jk.karyawan_id = ? AND jk.tanggal = ?
    LIMIT 1
");
$stmt->bind_param("is", $karyawan_id, $today);
$stmt->execute();
$jadwal = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get existing absensi today
$stmt = $connect->prepare("SELECT * FROM absensi WHERE karyawan_id = ? AND tanggal = ? LIMIT 1");
$stmt->bind_param("is", $karyawan_id, $today);
$stmt->execute();
$absenToday = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ============================================================
// ABSEN MASUK (no absensi yet today)
// ============================================================
if (!$absenToday) {
    $status_masuk    = 'tepat_waktu';
    $menit_terlambat = 0;

    if ($jadwal) {
        $jamMasukShift = strtotime($today . ' ' . $jadwal['jam_masuk']);
        $toleransi     = intval($jadwal['toleransi_menit']);
        $batasTelat    = $jamMasukShift + ($toleransi * 60);
        $nowTs         = time();

        if ($nowTs > $batasTelat) {
            $status_masuk    = 'terlambat';
            $menit_terlambat = round(($nowTs - $jamMasukShift) / 60);
        }
    }

    $jadwal_id = $jadwal ? $jadwal['id'] : null;

    $stmt = $connect->prepare("
        INSERT INTO absensi (karyawan_id, jadwal_id, tanggal, jam_masuk, status_masuk, menit_terlambat)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisssi", $karyawan_id, $jadwal_id, $today, $now, $status_masuk, $menit_terlambat);
    $stmt->execute();
    $stmt->close();

    $msg = 'Absen masuk berhasil!';
    if ($status_masuk === 'terlambat') {
        $msg .= " (Terlambat {$menit_terlambat} menit)";
    }

    echo json_encode([
        'success'      => true,
        'message'      => $msg,
        'status_masuk' => $status_masuk,
        'nama'         => $karyawan['fullName'],
    ]);
    exit;
}

// ============================================================
// ABSEN PULANG (masuk sudah ada, pulang belum)
// ============================================================
if ($absenToday && !$absenToday['jam_keluar']) {

    // Check if late departure (melewati jam pulang shift)
    $is_telat_pulang = false;
    $status_pulang   = 'tepat_waktu';

    if ($jadwal) {
        $jamPulangShift = strtotime($today . ' ' . $jadwal['jam_pulang']);
        $nowTs = time();

        if ($nowTs > $jamPulangShift) {
            $menitLewat = round(($nowTs - $jamPulangShift) / 60);
            if ($menitLewat > 5) { // toleransi 5 menit untuk pulang
                $is_telat_pulang = true;
                $status_pulang   = 'lembur';
            }
        } elseif ($nowTs < ($jamPulangShift - 15 * 60)) {
            $status_pulang = 'lebih_awal';
        }
    }

    // If late pulang and no reason given yet — ask for reason
    if ($is_telat_pulang && !$alasan_telat) {
        echo json_encode([
            'success'           => false,
            'need_alasan_telat' => true,
            'message'           => 'Harap isi alasan pulang terlambat',
        ]);
        exit;
    }

    // Update absen pulang
    $stmt = $connect->prepare("
        UPDATE absensi
        SET jam_keluar = ?, status_pulang = ?, alasan_telat_pulang = ?
        WHERE id = ?
    ");
    $alasan_val = $alasan_telat ?: null;
    $stmt->bind_param("sssi", $now, $status_pulang, $alasan_val, $absenToday['id']);
    $stmt->execute();
    $stmt->close();

    $msg = 'Absen pulang berhasil!';
    if ($status_pulang === 'lembur') $msg .= ' (Lembur)';
    if ($status_pulang === 'lebih_awal') $msg .= ' (Pulang lebih awal)';

    echo json_encode([
        'success'       => true,
        'message'       => $msg,
        'status_pulang' => $status_pulang,
        'nama'          => $karyawan['fullName'],
    ]);
    exit;
}

// Already fully attended today
echo json_encode(['success' => false, 'message' => 'Absensi hari ini sudah lengkap']);
