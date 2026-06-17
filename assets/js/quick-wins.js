/**
 * SPMB Quick Wins - Utility Functions
 * Includes: Loading states, Keyboard shortcuts, Bulk actions, Undo
 */

// ==========================================
// 1. GLOBAL LOADING OVERLAY
// ==========================================
const LoadingOverlay = {
    show(message = 'Memuat...') {
        let overlay = document.getElementById('global-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'global-loading-overlay';
            overlay.innerHTML = `
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <div class="loading-message">${message}</div>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        overlay.querySelector('.loading-message').textContent = message;
        overlay.classList.add('active');
    },
    hide() {
        const overlay = document.getElementById('global-loading-overlay');
        if (overlay) overlay.classList.remove('active');
    },
    update(message) {
        const msg = document.querySelector('#global-loading-overlay .loading-message');
        if (msg) msg.textContent = message;
    }
};

// ==========================================
// 2. KEYBOARD SHORTCUTS
// ==========================================
const KeyboardShortcuts = {
    enabled: true,
    shortcuts: {},

    init(customShortcuts = {}) {
        this.shortcuts = {
            'ctrl+s': () => {
                const form = document.querySelector('form[id="form-daftar"], form[id="form-edit"]');
                if (form) {
                    event.preventDefault();
                    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    showToast('Data disimpan!', 'success');
                }
            },
            'ctrl+f': () => {
                event.preventDefault();
                const searchInput = document.querySelector('input[type="search"], input[placeholder*="Cari"], input[placeholder*="Search"]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                } else {
                    showToast('Gunakan Ctrl+F lalu ketik untuk mencari', 'info');
                }
            },
            'ctrl+n': () => {
                if (event.target.tagName !== 'INPUT' && event.target.tagName !== 'TEXTAREA') {
                    event.preventDefault();
                    const inputPage = document.getElementById('page-input');
                    if (inputPage) {
                        const menuBtn = document.querySelector('[onclick*="input"]');
                        if (menuBtn) menuBtn.click();
                    }
                    showToast('Membuka form input baru', 'info');
                }
            },
            'escape': () => {
                Swal.close();
            },
            'ctrl+a': () => {
                // Select all checkboxes in bulk mode
                if (document.body.classList.contains('bulk-mode')) {
                    event.preventDefault();
                    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = true);
                    updateBulkCount();
                }
            },
            ...customShortcuts
        };

        document.addEventListener('keydown', (e) => {
            if (!this.enabled) return;
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                if (e.key === 'Escape') {
                    e.target.blur();
                    Swal.close();
                }
                return;
            }

            const key = [];
            if (e.ctrlKey || e.metaKey) key.push('ctrl');
            if (e.shiftKey) key.push('shift');
            if (e.altKey) key.push('alt');
            key.push(e.key.toLowerCase());

            const shortcutKey = key.join('+');
            if (this.shortcuts[shortcutKey]) {
                e.preventDefault();
                this.shortcuts[shortcutKey](e);
            }
        });
    },

    disable() { this.enabled = false; },
    enable() { this.enabled = true; }
};

// ==========================================
// 3. BULK ACTIONS
// ==========================================
const BulkActions = {
    selected: new Set(),

    init(options = {}) {
        this.options = {
            checkboxClass: '.row-checkbox',
            itemClass: '.bulk-item',
            countDisplay: '#bulk-count',
            actionsContainer: '#bulk-actions',
            onSelectChange: null,
            ...options
        };

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('row-checkbox')) {
                this.handleCheckboxChange(e.target);
            }
        });
    },

    handleCheckboxChange(checkbox) {
        const itemId = checkbox.dataset.id;
        if (checkbox.checked) {
            this.selected.add(itemId);
        } else {
            this.selected.delete(itemId);
        }
        this.updateUI();
    },

    selectAll() {
        document.querySelectorAll(this.options.checkboxClass).forEach(cb => {
            cb.checked = true;
            this.selected.add(cb.dataset.id);
        });
        this.updateUI();
    },

    deselectAll() {
        document.querySelectorAll(this.options.checkboxClass).forEach(cb => {
            cb.checked = false;
        });
        this.selected.clear();
        this.updateUI();
    },

    updateUI() {
        const count = this.selected.size;
        const countDisplay = document.querySelector(this.options.countDisplay);
        const actionsContainer = document.querySelector(this.options.actionsContainer);

        if (countDisplay) {
            countDisplay.textContent = count;
            countDisplay.style.display = count > 0 ? 'inline-flex' : 'none';
        }

        if (actionsContainer) {
            actionsContainer.style.display = count > 0 ? 'flex' : 'none';
        }

        // Toggle body class for styling
        document.body.classList.toggle('bulk-mode', count > 0);

        if (this.options.onSelectChange) {
            this.options.onSelectChange(count, Array.from(this.selected));
        }
    },

    getSelected() {
        return Array.from(this.selected);
    },

    async executeBulkAction(action, confirmMsg = 'Yakin?') {
        const ids = this.getSelected();
        if (ids.length === 0) return;

        const result = await Swal.fire({
            title: 'Konfirmasi Bulk Action',
            html: `<p class="text-slate-600">${confirmMsg}</p><p class="font-bold text-indigo-600 mt-2">${ids.length} item dipilih</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'YA',
            cancelButtonText: 'TIDAK'
        });

        if (!result.isConfirmed) return false;

        LoadingOverlay.show(`Memproses ${ids.length} item...`);

        // Return the IDs for processing
        return ids;
    }
};

// Global function for checkbox updates
function updateBulkCount() {
    BulkActions.updateUI();
}

// ==========================================
// 4. UNDO SYSTEM (Session-based)
// ==========================================
const UndoSystem = {
    storageKey: 'spmb_undo_stack',
    maxItems: 10,

    init() {
        // Clean old entries on init
        this.cleanOldEntries();
    },

    push(action, data) {
        const stack = this.getStack();
        const entry = {
            action,
            data,
            timestamp: Date.now(),
            displayTime: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
        };

        stack.unshift(entry);
        if (stack.length > this.maxItems) {
            stack.pop();
        }

        localStorage.setItem(this.storageKey, JSON.stringify(stack));
        this.showUndoToast(action, entry);
    },

    getStack() {
        const stored = localStorage.getItem(this.storageKey);
        return stored ? JSON.parse(stored) : [];
    },

    pop() {
        const stack = this.getStack();
        if (stack.length === 0) return null;

        const entry = stack.shift();
        localStorage.setItem(this.storageKey, JSON.stringify(stack));
        return entry;
    },

    clear() {
        localStorage.removeItem(this.storageKey);
    },

    showUndoToast(action, entry) {
        const actionLabels = {
            'create': 'menambah data',
            'update': 'mengubah data',
            'delete': 'menghapus data',
            'acc': 'melakukan ACC'
        };

        const label = actionLabels[action] || action;

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `Berhasil ${label}`,
            text: entry.displayTime,
            showConfirmButton: true,
            confirmButtonText: 'URUNGKAN',
            timer: 5000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('click', () => {
                    const undone = this.pop();
                    if (undone) {
                        this.undoAction(undone);
                        Swal.close();
                    }
                });
            }
        });
    },

    async undoAction(entry) {
        // Implement undo based on action type
        switch (entry.action) {
            case 'create':
                // For create, we could delete the newly created item
                if (entry.data.id) {
                    await fetch('api_undo.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'undo_create', id: entry.data.id })
                    });
                }
                break;
            case 'delete':
                // For delete, we could restore the item
                if (entry.data) {
                    await fetch('api_undo.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'undo_delete', data: entry.data })
                    });
                }
                break;
        }

        showToast('Aksi di-undo', 'info');
        setTimeout(() => window.location.reload(), 500);
    },

    cleanOldEntries() {
        const stack = this.getStack();
        const oneHourAgo = Date.now() - (60 * 60 * 1000);
        const filtered = stack.filter(e => e.timestamp > oneHourAgo);
        if (filtered.length !== stack.length) {
            localStorage.setItem(this.storageKey, JSON.stringify(filtered));
        }
    }
};

// ==========================================
// 5. TOAST NOTIFICATIONS
// ==========================================
function showToast(message, type = 'info') {
    const colors = {
        success: { bg: 'bg-emerald-500', icon: 'fa-check-circle' },
        error: { bg: 'bg-red-500', icon: 'fa-times-circle' },
        warning: { bg: 'bg-amber-500', icon: 'fa-exclamation-circle' },
        info: { bg: 'bg-blue-500', icon: 'fa-info-circle' }
    };

    const color = colors[type] || colors.info;

    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 ${color.bg} text-white px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 z-[9999] transform translate-x-full opacity-0 transition-all duration-300`;
    toast.innerHTML = `
        <i class="fas ${color.icon}"></i>
        <span class="font-bold text-sm">${message}</span>
    `;

    document.body.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    });

    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ==========================================
// 6. KEYBOARD SHORTCUTS HINT
// ==========================================
function showKeyboardHints() {
    const hints = `
        <div class="text-left text-sm space-y-2">
            <div class="flex items-center gap-3">
                <kbd class="px-2 py-1 bg-slate-100 border border-slate-300 rounded text-xs font-mono">Ctrl + S</kbd>
                <span class="text-slate-600">Simpan data</span>
            </div>
            <div class="flex items-center gap-3">
                <kbd class="px-2 py-1 bg-slate-100 border border-slate-300 rounded text-xs font-mono">Ctrl + F</kbd>
                <span class="text-slate-600">Fokus pencarian</span>
            </div>
            <div class="flex items-center gap-3">
                <kbd class="px-2 py-1 bg-slate-100 border border-slate-300 rounded text-xs font-mono">Ctrl + N</kbd>
                <span class="text-slate-600">Form baru</span>
            </div>
            <div class="flex items-center gap-3">
                <kbd class="px-2 py-1 bg-slate-100 border border-slate-300 rounded text-xs font-mono">Esc</kbd>
                <span class="text-slate-600">Tutup popup</span>
            </div>
            <div class="flex items-center gap-3">
                <kbd class="px-2 py-1 bg-slate-100 border border-slate-300 rounded text-xs font-mono">Ctrl + A</kbd>
                <span class="text-slate-600">Pilih semua (bulk)</span>
            </div>
        </div>
    `;

    Swal.fire({
        title: '<span class="font-outfit font-black">Keyboard Shortcuts</span>',
        html: hints,
        icon: 'info',
        confirmButtonText: 'TUTUP',
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'bg-indigo-600 text-white px-4 py-2 rounded-xl font-bold text-xs'
        }
    });
}

// ==========================================
// INIT ON PAGE LOAD
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    UndoSystem.init();
    KeyboardShortcuts.init();

    // Add ? shortcut hint button if logged in
    const helpBtn = document.createElement('button');
    helpBtn.innerHTML = '<i class="fas fa-keyboard"></i>';
    helpBtn.className = 'fixed bottom-4 right-4 w-10 h-10 bg-white border border-slate-200 rounded-full shadow-lg flex items-center justify-center text-slate-500 hover:text-indigo-600 hover:border-indigo-300 transition-all z-50';
    helpBtn.onclick = showKeyboardHints;
    helpBtn.title = 'Keyboard Shortcuts (?)';
    document.body.appendChild(helpBtn);
});
