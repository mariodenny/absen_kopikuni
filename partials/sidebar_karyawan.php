<?php
$currentPage = isset($_GET['page']) ? $_GET['page'] : '';
function sidebarLink($page, $icon, $label, $current) {
    $active = ($current === $page) ? ' active' : '';
    return "<a href=\"index.php?page={$page}\" class=\"kopi-sidebar-link{$active}\">"
         . "<span class=\"kopi-sidebar-icon\">{$icon}</span> {$label}</a>";
}
?>
<aside class="kopi-sidebar" id="kopi-sidebar">
    <div class="kopi-sidebar-section">
        <div class="kopi-sidebar-label">Karyawan</div>
        <?= sidebarLink('karyawan/dashboard', '🏠', 'Dashboard', $currentPage) ?>
        <?= sidebarLink('absen', '📷', 'Absen Sekarang', $currentPage) ?>
        <?= sidebarLink('karyawan/history', '📅', 'History Absensi', $currentPage) ?>
        <?= sidebarLink('karyawan/izin', '📝', 'Pengajuan Izin', $currentPage) ?>
    </div>
    <div class="kopi-sidebar-section" style="margin-top:auto; padding:1rem .5rem; border-top:1px solid var(--kopi-border)">
        <a href="index.php?page=logout" class="kopi-sidebar-link" style="color:var(--kopi-danger)">
            <span class="kopi-sidebar-icon">🚪</span> Keluar
        </a>
    </div>
</aside>
