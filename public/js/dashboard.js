document.addEventListener('DOMContentLoaded', () => {
    const dashboardReservationsData = document.getElementById('dashboardReservationsData');
    const dashboardDataElement = document.getElementById('dashboardData');
    const dashboardReservations = dashboardReservationsData ? JSON.parse(dashboardReservationsData.textContent || '[]') : [];
    const dashboardAvailableRoomsData = document.getElementById('dashboardAvailableRoomsData');
    const dashboardAvailableRooms = dashboardAvailableRoomsData ? JSON.parse(dashboardAvailableRoomsData.textContent || '[]') : [];
    const currencyCode = dashboardDataElement?.dataset?.currency || '';
    const clientsSearchRoute = dashboardDataElement?.dataset?.clientsSearchRoute || '';
    const clientsQuickStoreRoute = dashboardDataElement?.dataset?.clientsQuickStoreRoute || '';
    const reservationUpdateBaseUrl = dashboardDataElement?.dataset?.reservationBaseUrl || '';
    const isOwner = dashboardDataElement?.dataset?.isOwner === '1';

    document.querySelectorAll('.room-progress').forEach(el => {
        el.style.width = `${el.dataset.width}%`;
    });

    const reservationModal = document.getElementById('reservationModal');
    const dashboardCheckinForm = document.getElementById('dashboardCheckinForm');
    const dashboardReservationSelect = document.getElementById('dashboardReservationSelect');
    const dashboardSelectedReservationInfo = document.getElementById('dashboardSelectedReservationInfo');
    const dashboardCheckinDate = document.getElementById('dashboardCheckinDate');
    const dashboardNoReservationsAlert = document.getElementById('dashboardNoReservationsAlert');
    const dashboardConfirmCheckinBtn = document.getElementById('dashboardConfirmCheckinBtn');
    const modalRoomId = document.getElementById('modalRoomId');
    const modalApartmentId = document.getElementById('modalApartmentId');
    const apartmentLabel = document.getElementById('apartmentLabel');

    const changeRoomModal = document.getElementById('changeRoomModal');
    const dashboardChangeRoomForm = document.getElementById('dashboardChangeRoomForm');
    const changeRoomCurrentLabel = document.getElementById('changeRoomCurrentLabel');
    const changeRoomApartmentLabel = document.getElementById('changeRoomApartmentLabel');
    const changeRoomAvailableList = document.getElementById('changeRoomAvailableList');
    const changeRoomNoRoomAlert = document.getElementById('changeRoomNoRoomAlert');
    const changeRoomConfirmBtn = document.getElementById('changeRoomConfirmBtn');
    const changeRoomToRoomId = document.getElementById('changeRoomToRoomId');

    let selectedChangeRoomId = null;

    const buildReservationLabel = (reservation) => {
        return `${reservation.reference} • ${reservation.client_name} • Entrée prévue ${reservation.expected_checkin_date || 'n/a'}`;
    };

    const populateReservationSelect = (apartmentId) => {
        if (!dashboardReservationSelect) return;

        const matching = dashboardReservations.filter((reservation) => String(reservation.apartment_id) === String(apartmentId));
        dashboardReservationSelect.innerHTML = '<option value="">Sélectionner une réservation</option>';

        if (matching.length === 0) {
            dashboardNoReservationsAlert?.classList.remove('d-none');
            dashboardConfirmCheckinBtn?.setAttribute('disabled', 'disabled');
            if (dashboardSelectedReservationInfo) {
                dashboardSelectedReservationInfo.textContent = 'Aucune réservation trouvée.';
            }
        } else {
            dashboardNoReservationsAlert?.classList.add('d-none');
            dashboardConfirmCheckinBtn?.removeAttribute('disabled');
            matching.forEach((reservation) => {
                const option = document.createElement('option');
                option.value = reservation.id;
                option.textContent = buildReservationLabel(reservation);
                dashboardReservationSelect.appendChild(option);
            });
            if (dashboardSelectedReservationInfo) {
                dashboardSelectedReservationInfo.textContent = 'Aucune réservation sélectionnée.';
            }
        }
    };

    const updateSelectedReservationInfo = () => {
        if (!dashboardReservationSelect || !dashboardSelectedReservationInfo || !dashboardCheckinForm) return;

        const selectedId = dashboardReservationSelect.value;
        if (!selectedId) {
            dashboardSelectedReservationInfo.textContent = 'Aucune réservation sélectionnée.';
            dashboardCheckinForm.action = '';
            return;
        }

        const selected = dashboardReservations.find((reservation) => String(reservation.id) === String(selectedId));
        if (!selected) {
            dashboardSelectedReservationInfo.textContent = 'Réservation introuvable.';
            dashboardCheckinForm.action = '';
            return;
        }

        dashboardSelectedReservationInfo.textContent = `${selected.reference} • ${selected.client_name} • Départ prévu ${selected.expected_checkout_date || 'n/a'}`;
        dashboardCheckinForm.action = `${reservationUpdateBaseUrl}/${selected.id}`;
    };

    if (reservationModal) {
        reservationModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            modalApartmentId.value = button.getAttribute('data-apartment-id');
            modalRoomId.value = button.getAttribute('data-room-id');
            apartmentLabel.textContent = button.getAttribute('data-apartment-name') ? `(${button.getAttribute('data-apartment-name')})` : '';

            const nowDate = new Date().toISOString().slice(0, 10);
            if (dashboardCheckinDate) {
                dashboardCheckinDate.value = nowDate;
                dashboardCheckinDate.readOnly = !isOwner;
            }

            populateReservationSelect(modalApartmentId.value);
            updateSelectedReservationInfo();
        });
    }

    const resetChangeRoomSelection = () => {
        selectedChangeRoomId = null;
        if (changeRoomToRoomId) changeRoomToRoomId.value = '';
        if (changeRoomConfirmBtn) changeRoomConfirmBtn.disabled = true;
        changeRoomAvailableList?.querySelectorAll('.list-group-item').forEach((item) => {
            item.classList.remove('active');
        });
    };

    const selectChangeRoom = (room, button) => {
        resetChangeRoomSelection();
        selectedChangeRoomId = room.id;
        if (changeRoomToRoomId) changeRoomToRoomId.value = room.id;
        if (changeRoomConfirmBtn) changeRoomConfirmBtn.disabled = false;
        if (button) button.classList.add('active');
    };

    const renderAvailableRooms = (apartmentId) => {
        if (!changeRoomAvailableList) return;
        changeRoomAvailableList.innerHTML = '';
        resetChangeRoomSelection();

        const matching = dashboardAvailableRooms.filter((room) => String(room.apartment_id) === String(apartmentId));
        if (matching.length === 0) {
            changeRoomNoRoomAlert?.classList.remove('d-none');
            return;
        }

        changeRoomNoRoomAlert?.classList.add('d-none');
        matching.forEach((room) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action';
            button.textContent = `#${room.number}`;
            button.addEventListener('click', () => selectChangeRoom(room, button));
            changeRoomAvailableList.appendChild(button);
        });
    };

    if (changeRoomModal) {
        changeRoomModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const reservationId = button.getAttribute('data-reservation-id');
            const currentRoomNumber = button.getAttribute('data-room-number');
            const apartmentId = button.getAttribute('data-apartment-id');
            const apartmentName = button.getAttribute('data-apartment-name');

            if (changeRoomCurrentLabel) changeRoomCurrentLabel.textContent = `Chambre ${currentRoomNumber}`;
            if (changeRoomApartmentLabel) changeRoomApartmentLabel.textContent = apartmentName || '-';
            if (dashboardChangeRoomForm) dashboardChangeRoomForm.action = `${reservationUpdateBaseUrl}/${reservationId}`;

            renderAvailableRooms(apartmentId);
        });
    }

    dashboardReservationSelect?.addEventListener('change', updateSelectedReservationInfo);

    const dashboardClientSearchInput = document.getElementById('dashboardClientSearchInput');
    const dashboardClientSelect = document.getElementById('dashboardClientSelect');
    const dashboardClientSearchFeedback = document.getElementById('dashboardClientSearchFeedback');
    const dashboardCreateClientModalEl = document.getElementById('dashboardCreateClientQuickModal');
    const dashboardClientCreateForm = document.getElementById('dashboardCreateClientQuickForm');
    const dashboardClientCreateFeedback = document.getElementById('dashboardClientCreateFeedback');

    const dashboardClients = [...(dashboardClientSelect?.options || [])]
        .filter((option) => option.value)
        .map((option) => ({ id: Number(option.value), name: option.textContent || '' }));

    const formatMoney = (value) => {
        const numeric = Number(value || 0);
        return `${numeric.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currencyCode}`;
    };

    const computeDashboardAmounts = () => {
        const nightly = Number(document.getElementById('modalApartmentPrice')?.value || 0);
        const checkinDate = document.getElementById('dashboardExpectedCheckinDate')?.value ? new Date(document.getElementById('dashboardExpectedCheckinDate').value) : null;
        const checkoutDate = document.getElementById('dashboardCheckoutDate')?.value ? new Date(document.getElementById('dashboardCheckoutDate').value) : null;
        let nights = 1;
        if (checkinDate && checkoutDate && checkoutDate >= checkinDate) {
            nights = Math.max(1, Math.round((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24)));
        }
        const gross = nightly * nights;
        const discount = Math.max(0, Number(document.getElementById('dashboardDiscountAmount')?.value || 0));
        const net = Math.max(0, gross - discount);

        document.getElementById('dashboardNightsCount').textContent = String(nights);
        document.getElementById('dashboardGrossAmount').textContent = formatMoney(gross);
        document.getElementById('dashboardDiscountPreview').textContent = formatMoney(discount);
        document.getElementById('dashboardNetAmount').textContent = formatMoney(net);
    };

    ['dashboardExpectedCheckinDate', 'dashboardCheckoutDate', 'dashboardDiscountAmount'].forEach((id) => {
        const el = document.getElementById(id);
        el?.addEventListener('input', computeDashboardAmounts);
        el?.addEventListener('change', computeDashboardAmounts);
    });
    computeDashboardAmounts();

    const setDashboardFeedback = (message, type = 'info') => {
        if (!dashboardClientSearchFeedback) return;
        dashboardClientSearchFeedback.classList.remove('text-danger', 'text-success', 'text-muted', 'text-warning');
        if (type === 'error') dashboardClientSearchFeedback.classList.add('text-danger');
        else if (type === 'success') dashboardClientSearchFeedback.classList.add('text-success');
        else if (type === 'warning') dashboardClientSearchFeedback.classList.add('text-warning');
        else dashboardClientSearchFeedback.classList.add('text-muted');
        dashboardClientSearchFeedback.textContent = message;
    };

    const renderDashboardClients = (clients) => {
        if (!dashboardClientSelect) return;
        dashboardClientSelect.innerHTML = '<option value="">Sélectionner</option>';
        clients.forEach((client) => {
            const option = document.createElement('option');
            option.value = String(client.id);
            option.textContent = client.name;
            dashboardClientSelect.appendChild(option);
        });

        if (clients.length > 0) {
            dashboardClientSelect.value = String(clients[0].id);
        }
    };

    let dashboardRemoteDebounce;

    const remoteDashboardSearch = async (term) => {
        const query = term.trim();
        if (!query) {
            return;
        }

        try {
            const response = await fetch(`${clientsSearchRoute}?q=${encodeURIComponent(query)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            const clients = data?.clients || [];
            if (clients.length > 0) {
                renderDashboardClients(clients);
                setDashboardFeedback(`${clients.length} client(s) trouvé(s).`, 'success');
                return;
            }
            setDashboardFeedback('Aucun client trouvé. Utilisez "Nouveau client".', 'warning');
        } catch (error) {
            setDashboardFeedback('Erreur de recherche client.', 'error');
        }
    };

    dashboardClientSearchInput?.addEventListener('input', () => {
        const term = (dashboardClientSearchInput.value || '').trim().toLowerCase();
        if (!term) {
            renderDashboardClients(dashboardClients);
            setDashboardFeedback('', 'info');
            clearTimeout(dashboardRemoteDebounce);
            return;
        }

        const matches = dashboardClients.filter((client) => client.name.toLowerCase().includes(term));
        renderDashboardClients(matches);

        if (matches.length > 0) {
            setDashboardFeedback(`${matches.length} client(s) trouvé(s).`, 'success');
        } else {
            setDashboardFeedback('Recherche serveur en cours...', 'info');
        }

        clearTimeout(dashboardRemoteDebounce);
        dashboardRemoteDebounce = setTimeout(() => remoteDashboardSearch(term), 250);
    });

    const upsertDashboardClient = (client) => {
        if (!dashboardClientSelect || !client?.id) return;
        let option = [...dashboardClientSelect.options].find((opt) => Number(opt.value) === Number(client.id));
        if (!option) {
            option = document.createElement('option');
            option.value = String(client.id);
            dashboardClientSelect.appendChild(option);
        }
        option.textContent = client.name;
        dashboardClientSelect.value = String(client.id);
    };

    dashboardClientCreateForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitButton = dashboardClientCreateForm.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;
        if (dashboardClientCreateFeedback) {
            dashboardClientCreateFeedback.className = 'small mt-2 text-muted';
            dashboardClientCreateFeedback.textContent = 'Création en cours...';
        }

        const formData = new FormData(dashboardClientCreateForm);
        try {
            const response = await fetch(clientsQuickStoreRoute, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const data = await response.json();
            if (!response.ok) {
                const firstError = data?.errors ? Object.values(data.errors)[0][0] : 'Erreur de création client.';
                if (dashboardClientCreateFeedback) {
                    dashboardClientCreateFeedback.className = 'small mt-2 text-danger';
                    dashboardClientCreateFeedback.textContent = firstError;
                }
                return;
            }

            dashboardClients.push({ id: Number(data.client.id), name: data.client.name });
            upsertDashboardClient(data.client);
            if (dashboardClientCreateFeedback) {
                dashboardClientCreateFeedback.className = 'small mt-2 text-success';
                dashboardClientCreateFeedback.textContent = 'Client créé et sélectionné.';
            }
            dashboardClientCreateForm.reset();
            bootstrap.Modal.getOrCreateInstance(dashboardCreateClientModalEl).hide();
            bootstrap.Modal.getOrCreateInstance(reservationModal).show();
        } catch (error) {
            if (dashboardClientCreateFeedback) {
                dashboardClientCreateFeedback.className = 'small mt-2 text-danger';
                dashboardClientCreateFeedback.textContent = 'Erreur serveur. Réessayez.';
            }
        } finally {
            if (submitButton) submitButton.disabled = false;
        }
    });
});
