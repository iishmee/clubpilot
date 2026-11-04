const databaseStatus = document.querySelector('#database-status');
const databaseDetail = document.querySelector('#database-detail');
const databaseIndicator = document.querySelector('#database-indicator');

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

        databaseStatus.textContent = 'Database connected';
        databaseDetail.textContent = 'The Laravel backend successfully queried the configured database.';
        databaseIndicator.className = 'database-indicator connected';
    } catch (error) {
        databaseStatus.textContent = 'Connection unavailable';
        databaseDetail.textContent = error instanceof Error
            ? error.message
            : 'Could not reach the backend API.';
        databaseIndicator.className = 'database-indicator disconnected';
    }
}

checkDatabaseConnection();
