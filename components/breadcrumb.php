<?php
/**
 * Breadcrumb Template Component
 * Usage: include 'breadcrumb.php' with $breadcrumbs array
 *
 * $breadcrumbs = [
 *     ['label' => 'Home', 'url' => '../../'],
 *     ['label' => 'Database', 'url' => 'index.php'],
 *     ['label' => 'Edit Siswa', 'active' => true]
 * ];
 */

if (!isset($breadcrumbs)) {
    $breadcrumbs = [
        ['label' => 'Home', 'url' => '../../']
    ];
}
?>
<nav class="breadcrumb mb-6 flex items-center gap-2 text-sm" aria-label="Breadcrumb">
    <?php foreach ($breadcrumbs as $i => $crumb): ?>
        <?php if ($i > 0): ?>
            <span class="breadcrumb-separator text-slate-300" aria-hidden="true">
                <i class="fas fa-chevron-right text-[8px]"></i>
            </span>
        <?php endif; ?>

        <?php if (isset($crumb['active']) && $crumb['active']): ?>
            <span class="breadcrumb-current text-slate-700 font-semibold flex items-center gap-1.5">
                <?php if (isset($crumb['icon'])): ?>
                    <i class="<?= $crumb['icon'] ?> text-xs"></i>
                <?php endif; ?>
                <?= htmlspecialchars($crumb['label']) ?>
            </span>
        <?php else: ?>
            <a href="<?= htmlspecialchars($crumb['url'] ?? '#') ?>"
               class="breadcrumb-link text-slate-500 hover:text-indigo-600 transition-colors flex items-center gap-1.5
                      <?= isset($crumb['class']) ? $crumb['class'] : '' ?>">
                <?php if ($i === 0): ?>
                    <i class="fas fa-home text-xs"></i>
                <?php elseif (isset($crumb['icon'])): ?>
                    <i class="<?= $crumb['icon'] ?> text-xs"></i>
                <?php endif; ?>
                <?= htmlspecialchars($crumb['label']) ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>

<style>
.breadcrumb {
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.breadcrumb-link:hover {
    text-decoration: none;
}

.breadcrumb-current {
    position: relative;
}

.breadcrumb-current::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
    border-radius: 1px;
}

@media (max-width: 640px) {
    .breadcrumb {
        font-size: 0.75rem;
    }
    .breadcrumb-link,
    .breadcrumb-current {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}
</style>
