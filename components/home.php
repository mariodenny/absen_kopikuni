<!-- Redirect logged-in users to their dashboard -->
<?php
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    $redirect = ($role === 'admin') ? 'admin/dashboard' : 'karyawan/dashboard';
    header("Location: index.php?page=$redirect");
    exit;
}
?>

<!-- Hero Landing Page -->
<div style="min-height:calc(100vh - 65px); display:flex; flex-direction:column; align-items:center; justify-content:center; padding:3rem 1.5rem; position:relative; overflow:hidden">

    <!-- Background Orbs -->
    <div style="position:absolute; width:600px; height:600px; background:radial-gradient(circle, rgba(139,69,19,0.1) 0%, transparent 70%); top:-100px; right:-100px; pointer-events:none"></div>
    <div style="position:absolute; width:400px; height:400px; background:radial-gradient(circle, rgba(212,168,83,0.07) 0%, transparent 70%); bottom:-50px; left:-50px; pointer-events:none"></div>

    <div style="max-width:680px; text-align:center; position:relative; z-index:1" class="fade-in">
        <!-- Logo -->
        <div style="font-size:4rem; margin-bottom:1rem">☕</div>

        <!-- Brand -->
        <h1 style="font-size:3rem; font-weight:900; letter-spacing:-0.03em; margin-bottom:.5rem; background:linear-gradient(135deg, #D4A853 0%, #8B4513 60%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text">
            Absensi Kopikuni
        </h1>

        <p style="font-size:1.1rem; color:var(--kopi-muted); margin-bottom:2.5rem; line-height:1.7">
            Sistem manajemen absensi karyawan berbasis web.<br>
            Scan QR · Verifikasi · Rekap — semua dalam satu platform.
        </p>

        <!-- CTA Buttons -->
        <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; margin-bottom:3rem">
            <a href="index.php?page=login" class="btn-kopi btn-kopi-primary btn-kopi-lg">
                🚀 Mulai Sekarang
            </a>
            <a href="index.php?page=register" class="btn-kopi btn-kopi-ghost btn-kopi-lg">
                📝 Daftar Akun
            </a>
        </div>

        <!-- Feature Cards -->
        <div class="kopi-grid grid-3" style="gap:1rem; text-align:left">
            <?php
            $features = [
                ['📷', 'Absen via QR Kamera', 'Scan QR barcode dengan kamera browser. Otomatis deteksi masuk atau pulang.'],
                ['⏰', 'Shift Fleksibel', 'Pagi & Siang dengan toleransi 15 menit. Otomatis hitung keterlambatan.'],
                ['✅', 'Verifikasi Admin', 'Admin dapat memverifikasi absensi dan menyetujui pengajuan izin.'],
                ['📊', 'Rekap Lengkap', 'Laporan kehadiran per karyawan, filter tanggal dan jabatan.'],
                ['📅', 'History Personal', 'Setiap karyawan bisa lihat history absensi mereka sendiri.'],
                ['📝', 'Pengajuan Izin', 'Ajukan izin/sakit/cuti dengan upload bukti JPG, PNG, atau PDF.'],
            ];
            foreach ($features as [$icon, $title, $desc]):
            ?>
            <div style="background:var(--kopi-surface); border:1px solid var(--kopi-border); border-radius:var(--radius-lg); padding:1.25rem">
                <div style="font-size:1.5rem; margin-bottom:.6rem"><?= $icon ?></div>
                <div style="font-weight:700; color:var(--kopi-cream); margin-bottom:.4rem; font-size:.9rem"><?= $title ?></div>
                <div style="font-size:.8rem; color:var(--kopi-muted); line-height:1.5"><?= $desc ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>