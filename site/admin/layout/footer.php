    </main>

<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Global function to update icons if dynamic content is added
    window.refreshIcons = () => {
        lucide.createIcons();
    }

    // Custom File Input Label Handler
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('file-input-real')) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'انتخاب تصویر...';
            const label = e.target.closest('.file-input-custom').querySelector('.file-name-label');
            if (label) label.textContent = fileName;
        }
    });
</script>
</body>
</html>
