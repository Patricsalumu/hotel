<x-app-layout>
    <x-slot name="header"><h4 class="mb-0">Administration - Chambres</h4></x-slot>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('owner.rooms.store') }}" class="row g-2">
            @csrf
            <div class="col-md-2">
                <select name="apartment_id" class="form-select" required>
                    <option value="">Appartement</option>
                    @foreach($apartments as $apartment)
                        <option value="{{ $apartment->id }}" @selected((string) old('apartment_id') === (string) $apartment->id)>{{ $apartment->name }} ({{ $apartment->floor_number }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1"><input name="number" class="form-control" placeholder="Numéro" value="{{ old('number') }}" required></div>
            <div class="col-md-2">
                <select name="type" class="form-select" required>
                    <option value="">Type</option>
                    <option value="simple" @selected(old('type') === 'simple')>Simple</option>
                    <option value="double" @selected(old('type') === 'double')>Double</option>
                    <option value="suite" @selected(old('type') === 'suite')>Suite</option>
                </select>
            </div>
            <div class="col-md-2"><input name="price_per_night" type="number" step="0.01" class="form-control" placeholder="Prix" value="{{ old('price_per_night') }}" required></div>
            <div class="col-md-2"><input name="dimension_width" type="number" min="60" max="500" class="form-control" placeholder="Largeur (px)" value="{{ old('dimension_width', 120) }}"></div>
            <div class="col-md-2"><input name="dimension_height" type="number" min="40" max="300" class="form-control" placeholder="Hauteur (px)" value="{{ old('dimension_height', 80) }}"></div>
            <div class="col-md-1"><button class="btn btn-primary w-100">Créer</button></div>
        </form>
    </div></div>

    <div class="card mb-3">
        <div class="card-header">Modifier une chambre</div>
        <div class="card-body">
            @if($rooms->isEmpty())
                <div class="text-muted">Aucune chambre à modifier pour le moment.</div>
            @else
                <form method="POST" id="editRoomForm" action="{{ route('owner.rooms.update', $rooms->first()) }}" class="row g-2">
                    @csrf
                    @method('PUT')
                    <div class="col-md-3">
                        <label class="form-label">Chambre</label>
                        <select id="edit_room_id" class="form-select" required>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}">{{ $r->number }} - {{ $r->apartment->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Appartement</label>
                        <select name="apartment_id" id="edit_apartment_id" class="form-select" required>
                            @foreach($apartments as $apartment)
                                <option value="{{ $apartment->id }}">{{ $apartment->name }} ({{ $apartment->floor_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1"><label class="form-label">Numéro</label><input name="number" id="edit_number" class="form-control" required></div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="type" id="edit_type" class="form-select" required>
                            <option value="simple">Simple</option>
                            <option value="double">Double</option>
                            <option value="suite">Suite</option>
                        </select>
                    </div>
                    <div class="col-md-1"><label class="form-label">Prix</label><input name="price_per_night" id="edit_price_per_night" type="number" step="0.01" class="form-control" required></div>
                    <div class="col-md-1"><label class="form-label">Larg.</label><input name="dimension_width" id="edit_dimension_width" type="number" min="60" max="500" class="form-control"></div>
                    <div class="col-md-1"><label class="form-label">Haut.</label><input name="dimension_height" id="edit_dimension_height" type="number" min="40" max="300" class="form-control"></div>
                    <div class="col-12 d-flex justify-content-end"><button class="btn btn-outline-primary">Enregistrer les modifications</button></div>
                </form>
            @endif
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Plan graphique des chambres (déplacer + redimensionner)</span>
            <button id="saveLayout" class="btn btn-sm btn-primary">Sauvegarder le plan</button>
        </div>
        <div class="card-body">
            <div class="small text-muted mb-2">Astuce: glissez une chambre pour la placer, utilisez la poignée en bas à droite pour agrandir/réduire, puis sauvegardez.</div>
            <div id="roomBoard" class="border rounded bg-white position-relative overflow-auto" style="height: 560px;">
                @foreach($rooms as $r)
                    @php
                        $width = 120;
                        $height = 80;
                        if ($r->dimension && preg_match('/^(\d+)x(\d+)$/', $r->dimension, $matches)) {
                            $width = (int) $matches[1];
                            $height = (int) $matches[2];
                        }
                    @endphp
                    <div
                        class="room-card position-absolute border rounded bg-light p-2"
                        data-room-id="{{ $r->id }}"
                        data-room-label="{{ $r->number }}"
                        style="left: {{ $r->position_x }}px; top: {{ $r->position_y }}px; width: {{ $width }}px; height: {{ $height }}px; cursor: move; user-select: none;"
                    >
                        <div class="fw-semibold">{{ $r->number }}</div>
                        <div class="small text-muted">{{ $r->apartment->name }}</div>
                        <div class="small">{{ ['occupied' => 'occupée', 'reserved' => 'réservée', 'available' => 'libre'][$r->status] ?? $r->status }}</div>
                        <div class="resize-handle position-absolute" style="right: 3px; bottom: 3px; width: 12px; height: 12px; background: #0d6efd; border-radius: 2px; cursor: nwse-resize;"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card table-responsive mt-3">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Appart.</th><th>Type</th><th>Prix</th><th>Dimension</th><th>Position</th></tr></thead>
            <tbody>
                @foreach($rooms as $r)
                    <tr>
                        <td>{{ $r->number }}</td>
                        <td>{{ $r->apartment->name }}</td>
                        <td>{{ ucfirst($r->type) }}</td>
                        <td>{{ number_format($r->price_per_night,2) }}</td>
                        <td>{{ $r->dimension ?? '-' }}</td>
                        <td>{{ $r->position_x }}, {{ $r->position_y }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @php
        $roomsDataForJs = $rooms->map(function ($room) {
            return [
                'id' => $room->id,
                'apartment_id' => $room->apartment_id,
                'number' => $room->number,
                'type' => $room->type,
                'price_per_night' => (string) $room->price_per_night,
                'dimension' => $room->dimension,
            ];
        })->values();
    @endphp

    <script>
        const roomsData = @json($roomsDataForJs);

        const updateRoomBaseUrl = "{{ url('/owner/rooms') }}";

        function parseDimension(dimension) {
            const match = (dimension || '').match(/^(\d+)x(\d+)$/);
            if (!match) return { width: 120, height: 80 };
            return { width: parseInt(match[1], 10), height: parseInt(match[2], 10) };
        }

        const editRoomForm = document.getElementById('editRoomForm');
        const editRoomSelect = document.getElementById('edit_room_id');
        const editApartment = document.getElementById('edit_apartment_id');
        const editNumber = document.getElementById('edit_number');
        const editType = document.getElementById('edit_type');
        const editPrice = document.getElementById('edit_price_per_night');
        const editWidth = document.getElementById('edit_dimension_width');
        const editHeight = document.getElementById('edit_dimension_height');

        function loadSelectedRoomIntoForm() {
            if (!editRoomSelect || !editRoomForm) return;
            const selectedId = parseInt(editRoomSelect.value, 10);
            const room = roomsData.find((item) => item.id === selectedId);
            if (!room) return;

            const dimension = parseDimension(room.dimension);

            editRoomForm.action = `${updateRoomBaseUrl}/${room.id}`;
            editApartment.value = String(room.apartment_id);
            editNumber.value = room.number;
            editType.value = room.type;
            editPrice.value = room.price_per_night;
            editWidth.value = dimension.width;
            editHeight.value = dimension.height;
        }

        if (editRoomSelect) {
            editRoomSelect.addEventListener('change', loadSelectedRoomIntoForm);
            loadSelectedRoomIntoForm();
        }

        const board = document.getElementById('roomBoard');
        let activeElement = null;
        let interactionMode = null;
        let startX = 0;
        let startY = 0;
        let startLeft = 0;
        let startTop = 0;
        let startWidth = 0;
        let startHeight = 0;

        const minWidth = 60;
        const minHeight = 40;

        function clamp(value, min, max) {
            return Math.max(min, Math.min(max, value));
        }

        if (board) {
            board.querySelectorAll('.room-card').forEach((card) => {
                card.addEventListener('mousedown', (event) => {
                    if (event.button !== 0) return;

                    activeElement = card;
                    interactionMode = event.target.closest('.resize-handle') ? 'resize' : 'drag';

                    startX = event.clientX;
                    startY = event.clientY;
                    startLeft = parseInt(card.style.left || '0', 10);
                    startTop = parseInt(card.style.top || '0', 10);
                    startWidth = card.offsetWidth;
                    startHeight = card.offsetHeight;

                    event.preventDefault();
                });
            });

            document.addEventListener('mousemove', (event) => {
                if (!activeElement || !interactionMode) return;

                const dx = event.clientX - startX;
                const dy = event.clientY - startY;
                const maxLeft = Math.max(0, board.clientWidth - activeElement.offsetWidth);
                const maxTop = Math.max(0, board.clientHeight - activeElement.offsetHeight);

                if (interactionMode === 'drag') {
                    const newLeft = clamp(startLeft + dx, 0, maxLeft);
                    const newTop = clamp(startTop + dy, 0, maxTop);
                    activeElement.style.left = `${newLeft}px`;
                    activeElement.style.top = `${newTop}px`;
                }

                if (interactionMode === 'resize') {
                    const newWidth = clamp(startWidth + dx, minWidth, board.clientWidth - startLeft);
                    const newHeight = clamp(startHeight + dy, minHeight, board.clientHeight - startTop);
                    activeElement.style.width = `${newWidth}px`;
                    activeElement.style.height = `${newHeight}px`;
                }
            });

            document.addEventListener('mouseup', () => {
                activeElement = null;
                interactionMode = null;
            });
        }

        document.getElementById('saveLayout').addEventListener('click', async () => {
            const cards = [...board.querySelectorAll('.room-card')];

            const sortedByVisualOrder = [...cards].sort((a, b) => {
                const ay = parseInt(a.style.top || '0', 10);
                const by = parseInt(b.style.top || '0', 10);
                if (ay !== by) return ay - by;

                const ax = parseInt(a.style.left || '0', 10);
                const bx = parseInt(b.style.left || '0', 10);
                return ax - bx;
            });

            const orderById = new Map(
                sortedByVisualOrder.map((card, index) => [parseInt(card.getAttribute('data-room-id'), 10), index])
            );

            const rooms = cards.map((card) => {
                const roomId = parseInt(card.getAttribute('data-room-id'), 10);
                const positionX = parseInt(card.style.left || '0', 10);
                const positionY = parseInt(card.style.top || '0', 10);
                const width = Math.round(card.offsetWidth);
                const height = Math.round(card.offsetHeight);

                return {
                    id: roomId,
                    position_x: positionX,
                    position_y: positionY,
                    order_index: orderById.get(roomId) ?? 0,
                    dimension: `${width}x${height}`
                };
            });

            const response = await fetch("{{ route('owner.rooms.layout.update') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ rooms })
            });

            if (response.ok) {
                window.location.reload();
            } else {
                alert('Erreur de sauvegarde du plan');
            }
        });
    </script>
</x-app-layout>
