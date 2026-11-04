const statusTitle = document.querySelector('#status-title');
const statusDetail = document.querySelector('#status-detail');
const statusIndicator = document.querySelector('#status-indicator');

async function checkDatabaseConnection() {
    try {
        const response = await fetch('/api/health', {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });

        if (!response.ok) {
            throw new Error(`Backend returned HTTP ${response.status}`);
        }

        const result = await response.json();

        if (result.status !== 'ok' || result.database !== 'connected') {
            throw new Error('Backend reported an unsuccessful database check');
        }

        statusTitle.textContent = 'Database connected';
        statusDetail.textContent = 'The Laravel backend successfully queried the configured database.';
        statusIndicator.className = 'indicator connected';
        statusIndicator.setAttribute('aria-label', 'Connected');
    } catch (error) {
        statusTitle.textContent = 'Connection unavailable';
        statusDetail.textContent = error instanceof Error
            ? error.message
            : 'Could not reach the backend API.';
        statusIndicator.className = 'indicator disconnected';
        statusIndicator.setAttribute('aria-label', 'Disconnected');
    }
}

checkDatabaseConnection();
