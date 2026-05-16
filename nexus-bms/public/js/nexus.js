// === NEXUS BMS PLATFORM - MAIN JS ===

document.addEventListener('DOMContentLoaded', function () {
    // Sidebar toggle
    const sidebar = document.getElementById('nxSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const headerToggleBtn = document.getElementById('sidebarHeaderToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const collapseIcon = document.getElementById('collapseIcon');
    const isMobileSidebar = () => window.matchMedia('(max-width: 768px)').matches;
    const syncSidebarControls = () => {
        if (!sidebar) return;
        const collapsed = sidebar.classList.contains('collapsed');
        const open = sidebar.classList.contains('open');

        if (collapseIcon) {
            collapseIcon.className = collapsed && !isMobileSidebar()
                ? 'fa-solid fa-angles-right'
                : 'fa-solid fa-angles-left';
        }
        if (headerToggleBtn) {
            headerToggleBtn.setAttribute('aria-expanded', String(isMobileSidebar() ? open : !collapsed));
            headerToggleBtn.querySelector('i')?.classList.toggle('fa-xmark', isMobileSidebar() && open);
            headerToggleBtn.querySelector('i')?.classList.toggle('fa-bars', !(isMobileSidebar() && open));
        }
        sidebarBackdrop?.classList.toggle('show', isMobileSidebar() && open);
    };
    const toggleSidebar = () => {
        if (!sidebar) return;
        if (isMobileSidebar()) {
            sidebar.classList.toggle('open');
        } else {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }
        syncSidebarControls();
    };

    if (sidebar) {
        [toggleBtn, headerToggleBtn].forEach(btn => btn?.addEventListener('click', toggleSidebar));
        sidebarBackdrop?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            syncSidebarControls();
        });
        // Restore state
        if (!isMobileSidebar() && localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
        }
        window.addEventListener('resize', () => {
            if (!isMobileSidebar()) {
                sidebar.classList.remove('open');
            }
            syncSidebarControls();
        });
        syncSidebarControls();
    }

    // Active nav highlight
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(item => {
        const href = item.getAttribute('href') || '';
        if (href && currentPath.startsWith(href) && href !== '/') {
            item.classList.add('active');
        }
    });

    // Dropdown menus
    document.querySelectorAll('[data-dropdown]').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const target = document.getElementById(trigger.dataset.dropdown);
            if (target) target.classList.toggle('show');
        });
    });
    document.addEventListener('click', () => {
        document.querySelectorAll('.nx-dropdown-menu').forEach(m => m.classList.remove('show'));
    });

    // Alert dismissal
    document.querySelectorAll('[data-dismiss="alert"]').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.nx-alert')?.remove());
    });

    // Chip filter toggle
    document.querySelectorAll('.nx-chip[data-filter]').forEach(chip => {
        chip.addEventListener('click', () => {
            const group = chip.dataset.group || 'default';
            document.querySelectorAll(`.nx-chip[data-group="${group}"]`).forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
        });
    });

    // Tooltips (simple)
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', function () {
            const tip = document.createElement('div');
            tip.className = 'nx-tooltip';
            tip.textContent = this.dataset.tooltip;
            tip.style.cssText = 'position:absolute;background:#1e293b;color:white;padding:4px 8px;border-radius:4px;font-size:11px;z-index:9999;pointer-events:none;white-space:nowrap;';
            document.body.appendChild(tip);
            const rect = this.getBoundingClientRect();
            tip.style.left = rect.left + 'px';
            tip.style.top = (rect.top - 30 + window.scrollY) + 'px';
            this._tooltip = tip;
        });
        el.addEventListener('mouseleave', function () {
            this._tooltip?.remove();
        });
    });

    // Auto-hide flash messages
    setTimeout(() => {
        document.querySelectorAll('.nx-alert').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        });
    }, 4000);
});

// AJAX helpers
function nexusPost(url, data = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json());
}

function nexusFetch(url) {
    return fetch(url, { headers: { 'Accept': 'application/json' } }).then(r => r.json());
}

// Chart color palette
const NX_COLORS = {
    blue: '#1d4ed8', lightBlue: '#3b82f6', cyan: '#06b6d4',
    green: '#22c55e', yellow: '#f59e0b', orange: '#f97316',
    red: '#ef4444', purple: '#8b5cf6', slate: '#94a3b8',
    gradient: (ctx, color1, color2) => {
        const grad = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);
        grad.addColorStop(0, color1);
        grad.addColorStop(1, color2);
        return grad;
    }
};
