document.addEventListener('DOMContentLoaded', async () => {

    let users = [];
    let profiles = [];
    let usersTable = null;
    let editMode = false;
    let editId = null;

    const canCreate = Auth.can('users.create');
    const canEdit = Auth.can('users.edit');
    const canDelete = Auth.can('users.delete');
    const modal = document.getElementById('userModal');
    const openModal = document.getElementById('openUserModal');
    const closeModal = document.getElementById('closeUserProfileModal');
    const userForm = document.getElementById('userForm');
    const userModalTitle = document.getElementById('userModalTitle');
    const username = document.getElementById('username');
    const firstName = document.getElementById('first_name');
    const lastName = document.getElementById('last_name');
    const email = document.getElementById('email');
    const profileId = document.getElementById('profile_id');
    const password = document.getElementById('password');
    const passwordRequired = document.getElementById('passwordRequired');

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

    function clearErrors() {
        document.querySelectorAll('.wrapper-error')
            .forEach(el => el.classList.remove('wrapper-error'));

        document.querySelectorAll('.error-msg')
            .forEach(el => el.textContent = '');
    }

    function fieldWrapper(field) {
        const map = {
            username: 'wrapUsername',
            first_name: 'wrapFirstName',
            last_name: 'wrapLastName',
            email: 'wrapEmail',
            profile_id: 'wrapProfile',
            password: 'wrapPassword'
        };

        return document.getElementById(map[field] || '');
    }

    function showErrors(errors = {}) {
        Object.entries(errors).forEach(([field, message]) => {
            const wrapper = fieldWrapper(field);
            if (!wrapper) return;

            wrapper.classList.add('wrapper-error');

            const error = wrapper.querySelector('.error-msg');
            if (error) error.textContent = message;
        });
    }

    function showModal() {
        if (window.$ && $.fn.modal) {
            $('#userModal').modal('show');
            return;
        }

        modal.style.display = 'block';
    }

    function hideModal() {
        if (window.$ && $.fn.modal) {
            $('#userModal').modal('hide');
            return;
        }

        modal.style.display = 'none';
    }

    function resetUsersTable() {
        if (window.$ && $.fn.DataTable && $.fn.DataTable.isDataTable('#users')) {
            $('#users').DataTable().clear().destroy();
        }
    }

    function initUsersTable() {
        if (!(window.$ && $.fn.DataTable)) {
            return;
        }

        usersTable = $('#users').DataTable({
            pageLength: 10,
            ordering: false,
            searching: true,
            paging: true,
            lengthChange: false,
            pagingType: 'full_numbers',
            autoWidth: false,
            columnDefs: [
                { targets: 0, width: '130px' },
                { targets: 4, width: '150px', className: 'text-center' },
                { targets: 5, width: '150px', className: 'text-center' },
                { targets: 6, width: '150px', className: 'text-center', orderable: false }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/it-IT.json'
            }
        });
    }

    function appendCell(row, text) {
        const cell = document.createElement('td');
        cell.textContent = text ?? '';
        row.appendChild(cell);
        return cell;
    }

    function populateProfiles() {
        profileId.innerHTML = '';
        profileId.appendChild(new Option('Seleziona', ''));

        profiles.forEach(profile => {
            profileId.appendChild(new Option(profile.name, profile.id));
        });
    }

    function resetForm() {
        userForm.reset();
        clearErrors();
        editMode = false;
        editId = null;
        userModalTitle.textContent = 'Nuovo utente';
        passwordRequired.style.display = '';
    }

    function renderUsers() {
        const tbody = document.querySelector('#users tbody');
        resetUsersTable();
        tbody.innerHTML = '';

        users.forEach(user => {
            const row = document.createElement('tr');

            appendCell(row, user.username);
            appendCell(row, user.first_name || '-');
            appendCell(row, user.last_name || '-');
            appendCell(row, user.email || '-');
            appendCell(row, user.profile_name || '-').className = 'text-center';
            appendCell(row, user.created_at).className = 'text-center';

            const actionsCell = document.createElement('td');
            actionsCell.className = 'text-center';

            if (canEdit) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-xs btn-primary editUserBtn';
                button.dataset.id = user.id;
                button.textContent = 'Modifica';
                actionsCell.appendChild(button);
            }

            if (canDelete) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-xs btn-danger deleteUserBtn';
                button.dataset.id = user.id;
                button.textContent = 'Elimina';
                button.style.marginLeft = canEdit ? '5px' : '0';
                actionsCell.appendChild(button);
            }

            if (!canEdit && !canDelete) {
                actionsCell.textContent = '-';
            }

            row.appendChild(actionsCell);
            tbody.appendChild(row);
        });

        initUsersTable();
    }

    async function loadUsers() {
        const json = await fetchJSON('../api/v1/users');

        if (!json || !json.success || !json.data) {
            showToast(json?.error || 'Impossibile caricare gli utenti', 'error');
            return;
        }

        users = json.data.users || [];
        profiles = json.data.profiles || [];

        populateProfiles();
        renderUsers();
    }

    function payload() {
        return {
            username: username.value.trim(),
            first_name: firstName.value.trim(),
            last_name: lastName.value.trim(),
            email: email.value.trim(),
            profile_id: profileId.value,
            password: password.value
        };
    }

    function openEdit(id) {
        const user = users.find(item => String(item.id) === String(id));
        if (!user || !canEdit) return;

        resetForm();
        editMode = true;
        editId = id;
        userModalTitle.textContent = `Modifica ${user.username}`;
        passwordRequired.style.display = 'none';

        username.value = user.username || '';
        firstName.value = user.first_name || '';
        lastName.value = user.last_name || '';
        email.value = user.email || '';
        profileId.value = user.profile_id || '';
        password.value = '';

        showModal();
    }

    openModal.addEventListener('click', () => {
        if (!canCreate) return;

        resetForm();
        showModal();
    });

    closeModal.addEventListener('click', () => hideModal());

    document.addEventListener('click', async e => {
        const editButton = e.target.closest('.editUserBtn');
        if (editButton) {
            openEdit(editButton.dataset.id);
            return;
        }

        const deleteButton = e.target.closest('.deleteUserBtn');
        if (!deleteButton || !canDelete) return;

        const user = users.find(item => String(item.id) === String(deleteButton.dataset.id));
        const label = user
            ? `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.username
            : 'questo utente';

        if (!await AppDialog.confirm(`Eliminare ${label}?`)) {
            return;
        }

        const json = await fetchJSON(`../api/v1/users?id=${encodeURIComponent(deleteButton.dataset.id)}`, {
            method: 'DELETE'
        });

        if (!json) return;

        if (!json.success) {
            showToast(json.error || 'Eliminazione non riuscita', 'error');
            return;
        }

        await loadUsers();
        showToast('Utente eliminato', 'success');
    });

    userForm.addEventListener('submit', async e => {
        e.preventDefault();

        if ((editMode && !canEdit) || (!editMode && !canCreate)) {
            return;
        }

        clearErrors();

        const url = editMode
            ? `../api/v1/users?id=${encodeURIComponent(editId)}`
            : '../api/v1/users';

        const json = await fetchJSON(url, {
            method: editMode ? 'PUT' : 'POST',
            body: JSON.stringify(payload())
        });

        if (!json) return;

        if (!json.success) {
            showErrors(json.errors);
            showToast(json.error || 'Salvataggio non riuscito', 'error');
            return;
        }

        const wasEdit = editMode;

        hideModal();
        resetForm();
        await loadUsers();
        showToast(wasEdit ? 'Utente aggiornato' : 'Utente creato', 'success');
    });

    await loadUsers();
});
