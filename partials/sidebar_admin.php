<?php
$currentPage = isset($_GET['page']) ? $_GET['page'] : '';
function adminSidebarLink($page, $icon, $label, $current) {
    $active = ($current === $page) ? ' active' : '';
    return "<a href=\"index.php?page={$page}\" class=\"kopi-sidebar-link{$active}\">"
         . "<span class=\"kopi-sidebar-icon\">{$icon}</span> {$label}</a>";
}
?>
<aside class="kopi-sidebar" id="kopi-sidebar">
    <div class="kopi-sidebar-section">
        <div class="kopi-sidebar-label">Admin</div>
        <?= adminSidebarLink('admin/dashboard',        '🏠', 'Dashboard',        $currentPage) ?>
        <?= adminSidebarLink('admin/verifikasi',       '✅', 'Verifikasi Absen', $currentPage) ?>
        <?= adminSidebarLink('admin/rekap',            '📊', 'Rekap Data',       $currentPage) ?>
        <?= adminSidebarLink('admin/persetujuan_izin', '📋', 'Persetujuan Izin', $currentPage) ?>
    </div>
    <div class="kopi-sidebar-section">
        <div class="kopi-sidebar-label">Pengaturan</div>
        <?= adminSidebarLink('admin/karyawan_list',  '👥', 'Data Karyawan',  $currentPage) ?>
        <?= adminSidebarLink('admin/shift_settings', '⏰', 'Setting Shift',  $currentPage) ?>
    </div>
    <div style="padding:1rem .5rem; border-top:1px solid var(--kopi-border); margin-top:auto">
        <a href="index.php?page=logout" class="kopi-sidebar-link" style="color:var(--kopi-danger)">
            <span class="kopi-sidebar-icon">🚪</span> Keluar
        </a>
    </div>
</aside>
