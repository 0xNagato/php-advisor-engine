<script>
    (function() {
        const url = @json(route('heartbeat'));
        const INTERVAL_MS = 10 * 60 * 1000; // 10 minutes

        function ping() {
            if (document.hidden) return;
            if ('sendBeacon' in navigator) {
                try {
                    navigator.sendBeacon(url);
                } catch (e) {
                    console.error('sendBeacon heartbeat failed:', e);
                }
            } else {
                fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': @json(csrf_token()) } }).catch(() => {
                });
            }
        }

        setInterval(ping, INTERVAL_MS);
        window.addEventListener('focus', ping);
    })();
</script>

<script>
    // Livewire v3: intercept 419s globally while using Filament
    // Avoid the default confirm modal and simply refresh with a friendly notice.
    document.addEventListener('livewire:init', () => {
        if (window.Livewire && typeof Livewire.hook === 'function') {
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, preventDefault }) => {
                    if (status === 419) {
                        preventDefault();
                        const url = new URL(window.location.href);
                        url.searchParams.set('token_reset', '1');
                        window.location.replace(url.toString());
                    }
                });
            });
        }
    });
</script>
