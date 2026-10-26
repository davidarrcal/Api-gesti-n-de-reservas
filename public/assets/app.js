(() => {
    const feedback = document.querySelector('#feedback');
    const showMessage = (message, error = false) => { if (!feedback) return; feedback.textContent = message; feedback.classList.toggle('error', error); feedback.hidden = false; };
    const api = async (url, options = {}) => {
        const response = await fetch(url, { headers: { 'Content-Type': 'application/json', ...(options.headers || {}) }, ...options });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) { const details = payload.detalles ? Object.values(payload.detalles).join(' ') : ''; throw new Error(`${payload.error || 'Ha ocurrido un error.'} ${details}`.trim()); }
        return payload.datos;
    };

    document.querySelector('#filters')?.addEventListener('submit', async (event) => {
        event.preventDefault(); showMessage('Cargando...');
        try { const query = new URLSearchParams(new FormData(event.currentTarget)); const rows = await api(`/api/reservas?${query}`); 
        document.querySelector('#reservations-body').innerHTML = rows.map(row => `
            <tr><td>
            <strong>${escapeHtml(row.nombre_huesped)}</strong>
            <small>${escapeHtml(row.email_huesped)}</small>
            </td><td>${escapeHtml(row.nombre_alojamiento)}
                </td><td>${formatDate(row.fecha_entrada)} - ${formatDate(row.fecha_salida)}
                </td><td>${Number(row.importe).toFixed(2).replace('.', ',')} EUR
            </td><td>
            <span class="status status-${row.estado}">${row.estado.charAt(0).toUpperCase() + row.estado.slice(1)}
            </span>
            </td><td class="actions"><a href="/reservas/${row.id}">Ver</a>${row.estado !== 'cancelada' ? `<button class="link-button cancel-button" data-id="${row.id}">Cancelar</button>` : ''}
            </td></tr>`).join('') || '<tr><td colspan="6" class="empty">No hay reservas con esos filtros.</td></tr>'; 
            showMessage(`${rows.length} reserva(s) encontrada(s).`); } catch (error) { showMessage(error.message, true); }
    });

    document.querySelector('#reservation-form')?.addEventListener('submit', async (event) => {
        event.preventDefault(); const form = event.currentTarget; const data = Object.fromEntries(new FormData(form));
        if (data.fecha_salida <= data.fecha_entrada) { showMessage('La fecha de salida debe ser posterior a la entrada.', true); return; }
        showMessage('Guardando...');
        try { const reservation = await api('/api/reservas', { method: 'POST', body: JSON.stringify(data) }); window.location.href = `/reservas/${reservation.id}`; } catch (error) { showMessage(error.message, true); }
    });

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.cancel-button'); if (!button) return;
        if (!window.confirm('¿Quieres cancelar esta reserva?')) return;
        button.disabled = true;
        try { await api(`/api/reservas/${button.dataset.id}/estado`, { method: 'PATCH', body: JSON.stringify({ estado: 'cancelada' }) }); 
        showMessage('Reserva cancelada correctamente.'); setTimeout(() => window.location.reload(), 500); } 
        catch (error) { button.disabled = false; showMessage(error.message, true); }
    });

    const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, character => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' }[character]));
    const formatDate = (value) => { const [year, month, day] = value.split('-'); return `${day}/${month}/${year}`; };
})();
