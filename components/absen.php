<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../php/connection.php';

$user        = $_SESSION['user'];
$karyawan_id = $user['karyawan_id'];

// Fetch today's jadwal & shift for this karyawan
$today = date('Y-m-d');
$jadwal = null;
$shift  = null;

if ($karyawan_id) {
    $stmt = $connect->prepare("
        SELECT jk.*, s.jam_masuk, s.jam_pulang, s.nama_shift, s.toleransi_menit
        FROM jadwal_karyawan jk
        JOIN shifts s ON s.id = jk.shift_id
        WHERE jk.karyawan_id = ? AND jk.tanggal = ?
        LIMIT 1
    ");
    $stmt->bind_param("is", $karyawan_id, $today);
    $stmt->execute();
    $jadwal = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Today's absensi
$absenToday = null;
if ($karyawan_id) {
    $stmt = $connect->prepare("SELECT * FROM absensi WHERE karyawan_id = ? AND tanggal = ? LIMIT 1");
    $stmt->bind_param("is", $karyawan_id, $today);
    $stmt->execute();
    $absenToday = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// QR code of this karyawan (for display)
$qrToken = null;
if ($karyawan_id) {
    $stmt = $connect->prepare("SELECT qr_code FROM karyawan WHERE id = ?");
    $stmt->bind_param("i", $karyawan_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $qrToken = $row['qr_code'] ?? null;
    $stmt->close();
}

$qrUrl = $qrToken
    ? "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrToken)
    : null;
?>

<div class="kopi-layout">
    <?php include __DIR__ . '/../partials/sidebar_karyawan.php'; ?>

    <main class="kopi-content fade-in">
        <div class="kopi-page-header">
            <h1 class="kopi-page-title">📷 Absensi</h1>
            <p class="kopi-page-sub"><?= date('l, d F Y') ?></p>
        </div>

        <!-- Status Absen Hari Ini -->
        <div class="kopi-grid grid-2 mb-4">
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">🕐</span>
                <div class="kopi-stat-label">Jam Masuk</div>
                <div class="kopi-stat-value" style="font-size:1.5rem">
                    <?= $absenToday ? date('H:i', strtotime($absenToday['jam_masuk'])) : '--:--' ?>
                </div>
                <div class="kopi-stat-sub">
                    <?php if ($absenToday): ?>
                        <span class="kopi-badge <?= $absenToday['status_masuk'] === 'tepat_waktu' ? 'badge-success' : 'badge-warning' ?>">
                            <?= $absenToday['status_masuk'] === 'tepat_waktu' ? '✅ Tepat Waktu' : ('⚠️ Terlambat ' . $absenToday['menit_terlambat'] . ' menit') ?>
                        </span>
                    <?php else: ?>
                        <span class="kopi-badge badge-muted">Belum Absen</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">🕔</span>
                <div class="kopi-stat-label">Jam Pulang</div>
                <div class="kopi-stat-value" style="font-size:1.5rem">
                    <?= ($absenToday && $absenToday['jam_keluar']) ? date('H:i', strtotime($absenToday['jam_keluar'])) : '--:--' ?>
                </div>
                <div class="kopi-stat-sub">
                    <?php if ($absenToday && $absenToday['jam_keluar']): ?>
                        <span class="kopi-badge badge-success">✅ Sudah Pulang</span>
                    <?php elseif ($absenToday): ?>
                        <span class="kopi-badge badge-info">🔵 Sedang Bekerja</span>
                    <?php else: ?>
                        <span class="kopi-badge badge-muted">—</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($jadwal): ?>
        <div class="kopi-alert kopi-alert-info mb-4">
            📋 <strong>Shift Hari Ini:</strong> <?= htmlspecialchars($jadwal['nama_shift']) ?>
            — Masuk: <strong><?= substr($jadwal['jam_masuk'], 0, 5) ?></strong>
            &nbsp;|&nbsp; Pulang: <strong><?= substr($jadwal['jam_pulang'], 0, 5) ?></strong>
            &nbsp;|&nbsp; Toleransi: <strong><?= $jadwal['toleransi_menit'] ?> menit</strong>
        </div>
        <?php else: ?>
        <div class="kopi-alert kopi-alert-warning mb-4">
            ⚠️ Belum ada jadwal shift untuk hari ini. Hubungi admin.
        </div>
        <?php endif; ?>

        <!-- QR CODE MILIK KARYAWAN -->
        <?php if ($qrUrl): ?>
        <div class="kopi-card mb-4">
            <div class="kopi-card-header">
                <h3 class="kopi-card-title">📄 QR Code Absensi Kamu</h3>
                <span class="kopi-badge badge-gold">Tunjukkan ke Scanner</span>
            </div>
            <div class="kopi-card-body text-center">
                <img src="<?= $qrUrl ?>" alt="QR Code Absensi"
                     style="width:180px; height:180px; border-radius:12px; border:3px solid var(--kopi-border); image-rendering:pixelated">
                <p class="text-muted text-sm mt-2">Scan QR ini saat masuk & pulang</p>
                <a href="<?= $qrUrl ?>" download="qr_absensi_<?= htmlspecialchars($user['username']) ?>.png"
                   class="btn-kopi btn-kopi-ghost btn-kopi-sm mt-2">⬇️ Download QR</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- SCANNER KAMERA -->
        <?php if (!($absenToday && $absenToday['jam_keluar'])): ?>
        <div class="kopi-card">
            <div class="kopi-card-header">
                <h3 class="kopi-card-title">📷 Scan QR Absen</h3>
                <span id="scan-mode-badge" class="kopi-badge badge-info">
                    <?= !$absenToday ? '🟢 Mode: Masuk' : '🔴 Mode: Pulang' ?>
                </span>
            </div>
            <div class="kopi-card-body">
                <!-- Result message -->
                <div id="scan-result" style="display:none" class="mb-3"></div>

                <div class="qr-scanner-container" id="scanner-container">
                    <video id="qr-video" autoplay muted playsinline></video>
                    <div class="qr-overlay">
                        <div class="qr-frame">
                            <span></span>
                            <div class="qr-scan-line"></div>
                        </div>
                    </div>
                </div>

                <p class="text-center text-muted text-sm mt-2">Arahkan kamera ke QR Code karyawan</p>

                <div class="flex-center gap-2 mt-3">
                    <button id="btn-start-scan" class="btn-kopi btn-kopi-primary" onclick="startScanner()">
                        ▶ Mulai Kamera
                    </button>
                    <button id="btn-stop-scan" class="btn-kopi btn-kopi-ghost" onclick="stopScanner()" style="display:none">
                        ⏹ Stop
                    </button>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="kopi-card">
            <div class="kopi-card-body text-center" style="padding:3rem">
                <div style="font-size:4rem; margin-bottom:1rem">✅</div>
                <h3 style="font-weight:800; color:var(--kopi-cream)">Absensi Hari Ini Selesai</h3>
                <p class="text-muted mt-1">Kamu sudah absen masuk dan pulang hari ini.</p>
                <a href="index.php?page=karyawan/history" class="btn-kopi btn-kopi-primary mt-3">Lihat History →</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- FORM ALASAN TELAT PULANG (modal) -->
        <div class="kopi-modal-overlay" id="modal-telat-pulang">
            <div class="kopi-modal">
                <h3 class="kopi-modal-title">⚠️ Konfirmasi Pulang Terlambat</h3>
                <p class="text-muted text-sm mb-3">
                    Kamu pulang melebihi jam shift yang ditentukan. Mohon isi alasan di bawah ini.
                </p>
                <form id="form-telat-pulang">
                    <input type="hidden" name="qr_token" id="input-qr-token">
                    <div class="kopi-form-group">
                        <label class="kopi-label">Alasan Telat Pulang *</label>
                        <textarea name="alasan" id="input-alasan" class="kopi-textarea"
                            placeholder="Contoh: Menyelesaikan pekerjaan tambahan, diminta lembur oleh supervisor..." required></textarea>
                    </div>
                    <div class="flex gap-1">
                        <button type="submit" class="btn-kopi btn-kopi-primary">Kirim & Absen Pulang</button>
                        <button type="button" class="btn-kopi btn-kopi-ghost" onclick="closeModal()">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<!-- jsQR library -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
let videoStream = null;
let scanInterval = null;
const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d');
let scanning = false;
let processed = false;

function startScanner() {
    document.getElementById('btn-start-scan').style.display = 'none';
    document.getElementById('btn-stop-scan').style.display = '';
    scanning = true;
    processed = false;

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(stream => {
            videoStream = stream;
            const video = document.getElementById('qr-video');
            video.srcObject = stream;
            video.play();
            scanInterval = setInterval(scanFrame, 300);
        })
        .catch(err => {
            showResult('error', '❌ Tidak bisa akses kamera: ' + err.message);
            resetScanBtn();
        });
}

function stopScanner() {
    scanning = false;
    clearInterval(scanInterval);
    if (videoStream) videoStream.getTracks().forEach(t => t.stop());
    document.getElementById('btn-start-scan').style.display = '';
    document.getElementById('btn-stop-scan').style.display = 'none';
}

function scanFrame() {
    if (!scanning || processed) return;
    const video = document.getElementById('qr-video');
    if (video.readyState !== video.HAVE_ENOUGH_DATA) return;

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(imageData.data, imageData.width, imageData.height);

    if (code && code.data) {
        processed = true;
        stopScanner();
        handleQRResult(code.data);
    }
}

function handleQRResult(token) {
    showResult('info', '🔍 QR terdeteksi, memproses...');

    fetch('php/handlers/absen_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'qr_token=' + encodeURIComponent(token)
    })
    .then(r => r.json())
    .then(data => {
        if (data.need_alasan_telat) {
            // Show modal for late departure reason
            document.getElementById('input-qr-token').value = token;
            document.getElementById('modal-telat-pulang').classList.add('active');
        } else if (data.success) {
            showResult('success', '✅ ' + data.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            showResult('error', '❌ ' + (data.message || 'Gagal absen'));
            resetScanBtn();
        }
    })
    .catch(() => {
        showResult('error', '❌ Terjadi kesalahan jaringan');
        resetScanBtn();
    });
}

// Form telat pulang
document.getElementById('form-telat-pulang').addEventListener('submit', function(e) {
    e.preventDefault();
    const token  = document.getElementById('input-qr-token').value;
    const alasan = document.getElementById('input-alasan').value;

    fetch('php/handlers/absen_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'qr_token=' + encodeURIComponent(token) + '&alasan_telat=' + encodeURIComponent(alasan)
    })
    .then(r => r.json())
    .then(data => {
        closeModal();
        if (data.success) {
            showResult('success', '✅ ' + data.message);
            setTimeout(() => location.reload(), 2000);
        } else {
            showResult('error', '❌ ' + (data.message || 'Gagal'));
            resetScanBtn();
        }
    });
});

function closeModal() {
    document.getElementById('modal-telat-pulang').classList.remove('active');
    processed = false;
}

function showResult(type, msg) {
    const el = document.getElementById('scan-result');
    const cls = { success: 'kopi-alert-success', error: 'kopi-alert-error', info: 'kopi-alert-info' };
    el.className = 'kopi-alert ' + (cls[type] || 'kopi-alert-info');
    el.innerHTML = msg;
    el.style.display = '';
}

function resetScanBtn() {
    document.getElementById('btn-start-scan').style.display = '';
    document.getElementById('btn-stop-scan').style.display = 'none';
    processed = false;
}
</script>
