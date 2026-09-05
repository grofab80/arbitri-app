// ==========================
// AUTH CORE
// ==========================

const Auth = {

    pagePermissions: {
        dashboard: 'dashboard.view',
        movements: 'movements.view',
        balance: 'balance.view',
        matches: 'matches.view',
        designations: 'designations.view',
        seasons: 'seasons.view',
        competitions: 'competitions.view',
        teams: 'teams.view',
        referees: 'referees.view',
        fields: 'fields.view',
        import: 'import.view',
        settings: 'settings.view',
        permissions: 'permissions.view',
        users: 'users.view'
    },

    fallbackPagePermissions: {
        admin: [
            'dashboard.view',
            'movements.view',
            'balance.view',
            'matches.view',
            'designations.view',
            'seasons.view',
            'competitions.view',
            'teams.view',
            'referees.view',
            'fields.view',
            'import.view',
            'settings.view',
            'permissions.view',
            'users.view'
        ],
        user: [
            'dashboard.view',
            'movements.view',
            'matches.view',
            'designations.view'
        ]
    },

    token() {
        return localStorage.getItem('token');
    },

    profile() {
        const raw = localStorage.getItem('user_profile');
        if (!raw) return null;

        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    },

    isLogged() {
        return !!localStorage.getItem('token');
    },

    payload() {
        const token = this.token();
        if (!token) return null;

        const parts = token.split('.');
        if (parts.length !== 3) return null;

        try {
            const json = atob(parts[1].replace(/-/g, '+').replace(/_/g, '/'));
            return JSON.parse(json);
        } catch (e) {
            return null;
        }
    },

    role() {
        return this.profile()?.profile_code || this.payload()?.profile_code || null;
    },

    roleLabel() {
        const profileName = this.profile()?.profile_name || this.payload()?.profile_name || '';
        if (profileName) return profileName;

        const role = this.role();
        if (role === 'admin') return 'Admin';
        if (role === 'user') return 'User';

        return 'Profilo non definito';
    },

    fullName() {
        const payload = this.profile() || this.payload();
        const name = [
            payload?.first_name,
            payload?.last_name
        ].filter(Boolean).join(' ').trim();

        return name || 'Utente';
    },

    email() {
        return this.profile()?.email || this.payload()?.email || '';
    },

    isAdmin() {
        return this.role() === 'admin';
    },

    permissions() {
        const profilePermissions = this.profile()?.permissions;
        if (Array.isArray(profilePermissions)) {
            return profilePermissions;
        }

        const tokenPermissions = this.payload()?.permissions;
        if (Array.isArray(tokenPermissions)) {
            return tokenPermissions;
        }

        return [];
    },

    hasPermissionData() {
        return this.permissions().length > 0;
    },

    can(permission) {
        if (!permission) return true;
        if (this.isAdmin()) return true;

        const permissions = this.permissions();
        if (permissions.length > 0) {
            return permissions.includes(permission);
        }

        const fallback = this.fallbackPagePermissions[this.role()] || [];
        return fallback.includes(permission);
    },

    canViewPage(page) {
        return this.can(this.pagePermissions[page]);
    },

    login(token) {
        localStorage.setItem('token', token);
        localStorage.removeItem('user_profile');
        window.location.replace("dashboard");
    },

    logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user_profile');
        window.location.replace("login");
    },

    requireAuth() {

        if (!this.isLogged()) {
            window.location.replace("login");
        }

    },

    requireGuest() {

        if (this.isLogged()) {
            window.location.replace("dashboard");
        }

    },

    async refreshMe() {
        if (!this.isLogged()) {
            return null;
        }

        const json = await fetchJSON("../api/v1/me");

        if (json && json.success && json.data) {
            localStorage.setItem('user_profile', JSON.stringify(json.data));
            return json.data;
        }

        return null;
    }

};


// ==========================
// GLOBAL HELPERS
// ==========================

function authHeaders() {
    const headers = {
        "Content-Type": "application/json"
    };

    const token = Auth.token();
    if (token) {
        headers.Authorization = "Bearer " + token;
    }

    return headers;
}

const AppDialog = {

    ensureModal() {
        let modal = document.getElementById('appDialogModal');

        if (modal) {
            return modal;
        }

        modal = document.createElement('div');
        modal.id = 'appDialogModal';
        modal.className = 'modal fade';
        modal.tabIndex = -1;
        modal.setAttribute('role', 'dialog');
        modal.innerHTML = `
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <button type="button" class="close app-dialog-cancel" aria-label="Chiudi">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title" id="appDialogTitle">Conferma</h4>
                    </div>
                    <div class="modal-body">
                        <p id="appDialogMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default app-dialog-cancel">Annulla</button>
                        <button type="button" class="btn btn-primary app-dialog-ok">Conferma</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        return modal;
    },

    open({ title = 'Conferma', message = '', confirmText = 'Conferma', cancelText = 'Annulla', confirmClass = 'btn-primary', showCancel = true } = {}) {
        return new Promise(resolve => {
            const modal = this.ensureModal();
            const titleEl = modal.querySelector('#appDialogTitle');
            const messageEl = modal.querySelector('#appDialogMessage');
            const okButton = modal.querySelector('.app-dialog-ok');
            const cancelButtons = modal.querySelectorAll('.app-dialog-cancel');
            const cancelFooterButton = modal.querySelector('.modal-footer .app-dialog-cancel');

            titleEl.textContent = title;
            messageEl.textContent = message;
            okButton.textContent = confirmText;
            okButton.className = `btn app-dialog-ok ${confirmClass}`;
            cancelFooterButton.textContent = cancelText;
            cancelFooterButton.style.display = showCancel ? '' : 'none';

            let settled = false;

            const close = value => {
                if (settled) return;
                settled = true;

                okButton.removeEventListener('click', onOk);
                cancelButtons.forEach(button => button.removeEventListener('click', onCancel));

                if (window.$ && $.fn.modal) {
                    $(modal).one('hidden.bs.modal', () => resolve(value));
                    $(modal).modal('hide');
                    return;
                }

                modal.style.display = 'none';
                resolve(value);
            };

            const onOk = () => close(true);
            const onCancel = () => close(false);

            okButton.addEventListener('click', onOk);
            cancelButtons.forEach(button => button.addEventListener('click', onCancel));

            if (window.$ && $.fn.modal) {
                $(modal).modal({
                    backdrop: 'static',
                    keyboard: false
                });
                return;
            }

            modal.style.display = 'block';
        });
    },

    message(message, title = 'Attenzione') {
        return this.open({
            title,
            message,
            confirmText: 'OK',
            showCancel: false
        });
    },

    confirm(message, title = 'Conferma eliminazione') {
        return this.open({
            title,
            message,
            confirmText: 'Elimina',
            confirmClass: 'btn-danger',
            showCancel: true
        });
    },

    notify(message, type = 'info') {
        if (window.$ && $.toast) {
            $.toast({
                text: message,
                icon: type,
                position: 'bottom-left',
                hideAfter: 3000
            });
            return;
        }

        this.message(message, type === 'error' ? 'Errore' : 'Messaggio');
    }

};


// ==========================
// FETCH WRAPPER
// ==========================

async function fetchJSON(url, options = {}) {

    options.headers = {
        ...authHeaders(),
        ...(options.headers || {})
    };

    let res;

    try {
        res = await fetch(url, options);
    } catch (e) {
        return {
            success: false,
            error: "Errore di rete"
        };
    }

    if (res.status === 401) {
        Auth.logout();
        return;
    }

    const contentType = res.headers.get("content-type") || "";

    if (!contentType.includes("application/json")) {
        return {
            success: false,
            error: "Risposta non valida dal server"
        };
    }

    try {
        return await res.json();
    } catch (e) {
        return {
            success: false,
            error: "JSON non valido dal server"
        };
    }
}

async function loadCurrentSeasonBadge() {
    const badge = document.getElementById("currentSeasonBadge");
    if (!badge) return;

    const json = await fetchJSON("../api/v1/current-season");

    if (!json || !json.success || !json.data) {
        badge.innerText = "Stagione non configurata";
        return;
    }

    badge.innerText = "Stagione " + json.data.name;
}

async function loadNotifications() {
    const menu = document.getElementById('topbarNotificationsMenu');
    const badge = document.getElementById('topbarNotificationsBadge');
    const header = document.getElementById('topbarNotificationsHeader');
    const list = document.getElementById('topbarNotificationsList');
    const markAll = document.getElementById('markAllNotificationsRead');

    if (!menu || !badge || !header || !list || !markAll) {
        return;
    }

    const json = await fetchJSON('../api/v1/notifications');

    if (!json || !json.success || !json.data) {
        badge.style.display = 'none';
        header.textContent = 'Notifiche non disponibili';
        markAll.style.display = 'none';
        list.innerHTML = `
            <li>
                <a href="#">
                    <i class="fa fa-warning text-yellow"></i>
                    Impossibile caricare le notifiche
                </a>
            </li>
        `;
        return;
    }

    const unreadCount = Number(json.data.unread_count || 0);
    const notifications = Array.isArray(json.data.notifications)
        ? json.data.notifications
        : [];

    badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
    badge.style.display = unreadCount > 0 ? '' : 'none';
    header.textContent = unreadCount === 1
        ? 'Hai 1 notifica non letta'
        : `Hai ${unreadCount} notifiche non lette`;
    markAll.style.display = notifications.length > 0 ? '' : 'none';

    if (!notifications.length) {
        list.innerHTML = `
            <li>
                <a href="#">
                    <i class="fa fa-check text-green"></i>
                    Nessuna notifica operativa
                </a>
            </li>
        `;
        return;
    }

    list.innerHTML = '';
    notifications.forEach(notification => {
        const item = document.createElement('li');
        item.className = notification.is_read ? 'notification-read' : 'notification-unread';

        const link = document.createElement('a');
        link.href = notification.href || '#';
        link.className = 'topbar-notification-link';

        const icon = document.createElement('i');
        icon.className = `fa ${notificationIcon(notification.alert_code)} ${notificationIconColor(notification.alert_code)}`;

        const content = document.createElement('span');
        content.className = 'topbar-notification-content';

        const title = document.createElement('strong');
        title.textContent = notification.title || 'Notifica operativa';

        const message = document.createElement('small');
        message.textContent = notification.message || '';

        content.appendChild(title);
        content.appendChild(message);
        link.appendChild(icon);
        link.appendChild(content);

        link.addEventListener('click', async event => {
            event.preventDefault();

            await markNotificationRead(notification.id);

            if (notification.href) {
                window.location.href = notification.href;
                return;
            }

            loadNotifications();
        });

        item.appendChild(link);
        list.appendChild(item);
    });
}

async function markNotificationRead(notificationId) {
    if (!notificationId) {
        return false;
    }

    const json = await fetchJSON('../api/v1/notifications-read', {
        method: 'PUT',
        body: JSON.stringify({ notification_id: notificationId })
    });

    return !!(json && json.success);
}

async function markAllNotificationsRead() {
    const json = await fetchJSON('../api/v1/notifications-read-all', {
        method: 'PUT',
        body: JSON.stringify({})
    });

    if (json && json.success) {
        loadNotifications();
    }
}

function notificationIcon(alertCode) {
    const icons = {
        seasons_to_close: 'fa-calendar-times-o',
        matches_without_designation: 'fa-user-times',
        teams_without_field: 'fa-home',
        referees_without_address: 'fa-map-marker',
        fields_without_geocode: 'fa-location-arrow',
        competitions_without_teams: 'fa-users'
    };

    return icons[alertCode] || 'fa-bell-o';
}

function notificationIconColor(alertCode) {
    const danger = ['seasons_to_close', 'competitions_without_teams'];
    const warning = ['matches_without_designation'];

    if (danger.includes(alertCode)) {
        return 'text-red';
    }

    if (warning.includes(alertCode)) {
        return 'text-yellow';
    }

    return 'text-aqua';
}

function applyRoleVisibility() {
    const isAdmin = Auth.isAdmin();

    document.querySelectorAll('[data-admin-only]')
        .forEach(el => {
            el.style.display = isAdmin ? '' : 'none';
        });

    document.body.classList.toggle('role-admin', isAdmin);
    document.body.classList.toggle('role-user', !isAdmin);
}

function applyPermissionVisibility() {
    document.querySelectorAll('[data-permission]')
        .forEach(el => {
            el.style.display = Auth.can(el.dataset.permission) ? '' : 'none';
        });

    updateTreeviewVisibility();
    applyRoleVisibility();
    updateSidebarHeaders();
    markActiveSidebarGroup();
}

function updateTreeviewVisibility() {
    document.querySelectorAll('.sidebar-menu .treeview')
        .forEach(treeview => {
            const visibleChildren = Array.from(treeview.querySelectorAll('.treeview-menu > li'))
                .filter(child => child.style.display !== 'none');

            treeview.style.display = visibleChildren.length > 0 ? '' : 'none';
        });
}

function updateSidebarHeaders() {
    document.querySelectorAll('.sidebar-menu .header')
        .forEach(header => {
            let hasVisibleItem = false;
            let current = header.nextElementSibling;

            while (current && !current.classList.contains('header')) {
                if (current.tagName === 'LI' && current.style.display !== 'none') {
                    hasVisibleItem = true;
                    break;
                }

                current = current.nextElementSibling;
            }

            header.style.display = hasVisibleItem ? '' : 'none';
        });
}

function markActiveSidebarGroup() {
    const page = currentAdminPage();
    const activeLink = document.querySelector(`.sidebar-menu a[href="${page}"]`);

    if (!activeLink) {
        return;
    }

    const activeItem = activeLink.closest('li');
    if (activeItem) {
        activeItem.classList.add('active');
    }

    const treeview = activeLink.closest('.treeview');
    if (treeview) {
        treeview.classList.add('active', 'menu-open');

        const menu = treeview.querySelector('.treeview-menu');
        if (menu) {
            menu.style.display = 'block';
        }
    }
}

function currentAdminPage() {
    const lastSegment = window.location.pathname.split('/').filter(Boolean).pop() || '';
    return lastSegment.replace(/\.php$/, '') || 'dashboard';
}

function firstAllowedPage() {
    const order = [
        'dashboard',
        'movements',
        'balance',
        'matches',
        'designations',
        'seasons',
        'competitions',
        'teams',
        'referees',
        'fields',
        'import',
        'settings',
        'permissions',
        'users'
    ];

    return order.find(page => Auth.canViewPage(page)) || null;
}

function guardCurrentPage() {
    const page = currentAdminPage();

    if (page === 'login' || page === 'forbidden') {
        return;
    }

    const requiredPermission = Auth.pagePermissions[page];
    if (!requiredPermission || Auth.can(requiredPermission)) {
        return;
    }

    const allowedPage = firstAllowedPage();
    window.location.replace(allowedPage || 'forbidden');
}

function loadUserProfile() {
    const fullName = Auth.fullName();
    const userEmail = Auth.email();

    const name = document.getElementById("sidebarUserName");
    const email = document.getElementById("sidebarUserEmail");
    const role = document.getElementById("sidebarUserRole");
    const topbarName = document.getElementById("topbarUserName");
    const topbarFullName = document.getElementById("topbarUserFullName");
    const topbarEmail = document.getElementById("topbarUserEmail");
    const topbarRole = document.getElementById("topbarUserRole");

    if (name) {
        name.innerText = fullName;
    }

    if (email) {
        email.innerText = userEmail;
    }

    if (role) {
        role.innerText = 'Profilo ' + Auth.roleLabel();
    }

    if (topbarName) {
        topbarName.innerText = fullName;
    }

    if (topbarFullName) {
        topbarFullName.innerText = fullName;
    }

    if (topbarEmail) {
        topbarEmail.innerText = userEmail || 'ASDACA';
    }

    if (topbarRole) {
        topbarRole.innerText = 'Profilo ' + Auth.roleLabel();
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    await Auth.refreshMe();
    loadUserProfile();
    applyPermissionVisibility();
    guardCurrentPage();
    loadCurrentSeasonBadge();
    loadNotifications();

    const markAllNotificationsReadLink = document.getElementById('markAllNotificationsRead');
    if (markAllNotificationsReadLink) {
        markAllNotificationsReadLink.addEventListener('click', event => {
            event.preventDefault();
            markAllNotificationsRead();
        });
    }
});
