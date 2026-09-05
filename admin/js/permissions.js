document.addEventListener('DOMContentLoaded', async () => {

    let profiles = [];
    let permissions = [];
    let assignmentMap = new Map();
    let selectedProfile = null;

    const canManage = Auth.can('permissions.manage');
    const profilesList = document.getElementById('profilesList');
    const permissionsTitle = document.getElementById('permissionsTitle');
    const permissionsNotice = document.getElementById('permissionsNotice');
    const permissionsMatrix = document.getElementById('permissionsMatrix');
    const saveButton = document.getElementById('savePermissions');

    function showToast(message, type = 'info') {
        if (window.$ && $.toast) {
            $.toast({
                text: message,
                icon: type,
                position: 'bottom-left',
                hideAfter: 3000
            });
            return;
        }

        AppDialog.notify(message, type);
    }

    function scopeLabel(scope) {
        const labels = {
            page: 'Pagine',
            action: 'Azioni',
            system: 'Sistema'
        };

        return labels[scope] || scope || 'Altro';
    }

    function profilePermissionSet(profileId) {
        return assignmentMap.get(Number(profileId)) || new Set();
    }

    function isAdminProfile(profile) {
        return profile?.code === 'admin';
    }

    function hasPermission(profile, permission) {
        if (isAdminProfile(profile)) {
            return true;
        }

        return profilePermissionSet(profile.id).has(Number(permission.id));
    }

    function isDeletePermission(permission) {
        return String(permission.code || '').endsWith('.delete');
    }

    function isLockedForProfile(profile, permission) {
        return !isAdminProfile(profile) && isDeletePermission(permission);
    }

    function renderProfiles() {
        profilesList.innerHTML = '';

        profiles.forEach(profile => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item permissions-profile-item';
            item.dataset.id = profile.id;

            if (selectedProfile && String(selectedProfile.id) === String(profile.id)) {
                item.classList.add('active');
            }

            const title = document.createElement('strong');
            title.textContent = profile.name;

            const code = document.createElement('small');
            code.textContent = profile.code;

            item.append(title, code);
            profilesList.appendChild(item);
        });
    }

    function renderNotice() {
        permissionsNotice.style.display = 'none';
        permissionsNotice.textContent = '';

        if (!selectedProfile) {
            return;
        }

        if (isAdminProfile(selectedProfile)) {
            permissionsNotice.textContent = 'Il profilo admin ha sempre tutti i permessi attivi e non puo essere modificato.';
            permissionsNotice.style.display = 'block';
            return;
        }

        if (!canManage) {
            permissionsNotice.textContent = 'Hai accesso in sola lettura alla configurazione permessi.';
            permissionsNotice.style.display = 'block';
        }
    }

    function renderPermissions() {
        permissionsMatrix.innerHTML = '';

        if (!selectedProfile) {
            permissionsTitle.textContent = 'Permessi profilo';
            saveButton.style.display = 'none';
            return;
        }

        permissionsTitle.textContent = `Permessi profilo: ${selectedProfile.name}`;
        saveButton.style.display = canManage && !isAdminProfile(selectedProfile) ? '' : 'none';

        const grouped = permissions.reduce((acc, permission) => {
            const scope = permission.scope || 'action';
            acc[scope] = acc[scope] || [];
            acc[scope].push(permission);
            return acc;
        }, {});

        Object.entries(grouped).forEach(([scope, items]) => {
            const box = document.createElement('div');
            box.className = 'permissions-scope-box';

            const heading = document.createElement('h4');
            heading.textContent = scopeLabel(scope);

            const table = document.createElement('table');
            table.className = 'table table-bordered table-striped permissions-table';

            const thead = document.createElement('thead');
            thead.innerHTML = '<tr><th>Abilitato</th><th>Codice</th><th>Descrizione</th><th>Attivo</th></tr>';

            const tbody = document.createElement('tbody');

            items.forEach(permission => {
                const row = document.createElement('tr');

                const enabledCell = document.createElement('td');
                enabledCell.className = 'text-center';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'permission-checkbox';
                checkbox.dataset.id = permission.id;
                checkbox.checked = hasPermission(selectedProfile, permission);
                checkbox.disabled = !canManage || isAdminProfile(selectedProfile) || isLockedForProfile(selectedProfile, permission);
                enabledCell.appendChild(checkbox);

                const codeCell = document.createElement('td');
                codeCell.textContent = permission.code;

                const descriptionCell = document.createElement('td');
                descriptionCell.textContent = permission.description;

                if (isLockedForProfile(selectedProfile, permission)) {
                    const lock = document.createElement('small');
                    lock.className = 'text-muted permission-lock-note';
                    lock.textContent = ' (solo admin)';
                    descriptionCell.appendChild(lock);
                }

                const activeCell = document.createElement('td');
                activeCell.className = 'text-center';
                activeCell.innerHTML = Number(permission.active) === 1
                    ? '<span class="label label-success">Si</span>'
                    : '<span class="label label-default">No</span>';

                row.append(enabledCell, codeCell, descriptionCell, activeCell);
                tbody.appendChild(row);
            });

            table.append(thead, tbody);
            box.append(heading, table);
            permissionsMatrix.appendChild(box);
        });

        renderNotice();
    }

    function selectedPermissionIds() {
        return Array.from(document.querySelectorAll('.permission-checkbox:checked:not(:disabled)'))
            .map(checkbox => Number(checkbox.dataset.id));
    }

    async function loadPermissions() {
        const json = await fetchJSON('../api/v1/permissions');

        if (!json || !json.success || !json.data) {
            showToast(json?.error || 'Impossibile caricare i permessi', 'error');
            return;
        }

        profiles = json.data.profiles || [];
        permissions = json.data.permissions || [];
        assignmentMap = new Map();

        (json.data.assignments || []).forEach(row => {
            const profileId = Number(row.profile_id);
            const permissionId = Number(row.permission_id);

            if (!assignmentMap.has(profileId)) {
                assignmentMap.set(profileId, new Set());
            }

            assignmentMap.get(profileId).add(permissionId);
        });

        selectedProfile = selectedProfile
            ? profiles.find(profile => String(profile.id) === String(selectedProfile.id)) || profiles[0]
            : profiles[0];

        renderProfiles();
        renderPermissions();
    }

    profilesList.addEventListener('click', e => {
        const item = e.target.closest('.permissions-profile-item');
        if (!item) return;

        selectedProfile = profiles.find(profile => String(profile.id) === String(item.dataset.id));
        renderProfiles();
        renderPermissions();
    });

    saveButton.addEventListener('click', async () => {
        if (!selectedProfile || !canManage || isAdminProfile(selectedProfile)) {
            return;
        }

        const json = await fetchJSON(`../api/v1/profile-permissions?profile_id=${encodeURIComponent(selectedProfile.id)}`, {
            method: 'PUT',
            body: JSON.stringify({
                permission_ids: selectedPermissionIds()
            })
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Salvataggio non riuscito', 'error');
            return;
        }

        showToast('Permessi aggiornati', 'success');
        await loadPermissions();
    });

    await loadPermissions();
});
