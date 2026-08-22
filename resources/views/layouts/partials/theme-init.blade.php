<script>
    (function () {
        function applyTheme() {
            try {
                var stored = localStorage.getItem('theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var isDark = stored ? stored === 'dark' : prefersDark;
                document.documentElement.classList.toggle('dark', isDark);
            } catch (e) {}
        }

        applyTheme();
        document.addEventListener('livewire:navigated', applyTheme);
    })();
</script>
