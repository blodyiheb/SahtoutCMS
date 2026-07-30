        // Mobile menu toggle
        const toggleButton = document.querySelector('.nav-toggle');
        const closeButton = document.querySelector('.nav-close');
        const nav = document.querySelector('header nav');

        toggleButton.addEventListener('click', () => {
            nav.classList.toggle('nav-open');
        });

        closeButton.addEventListener('click', () => {
            nav.classList.remove('nav-open');
        });

        // Profile dropdown toggle
        const profileToggle = document.getElementById('profileToggle');
        const dropdownMenu = document.getElementById('dropdownMenu');

        if (profileToggle && dropdownMenu) {
            profileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
                document.getElementById('langOptions').classList.remove('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.profile-dropdown')) {
                    dropdownMenu.classList.remove('show');
                }
            });

            // Handle viewport changes
            function handleViewportChange() {
                if (window.matchMedia('(min-width: 769px)').matches) {
                    // Desktop view - hide dropdown
                    dropdownMenu.classList.remove('show');
                }
            }

            // Add event listener for viewport changes
            window.matchMedia('(min-width: 769px)').addEventListener('change', handleViewportChange);
            
            // Initial check
            handleViewportChange();
        }

        // Language dropdown toggle
        const langToggle = document.getElementById('langSelected');
        const langOptions = document.getElementById('langOptions');

        if (langToggle && langOptions) {
            langToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                langOptions.classList.toggle('show');
                if (dropdownMenu) {
                    dropdownMenu.classList.remove('show'); // Close profile menu when language is opened
                }
            });

            // Close language dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.lang-dropdown')) {
                    langOptions.classList.remove('show');
                }
            });

            // Language selection
            document.querySelectorAll('.lang-options li').forEach(option => {
                option.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const lang = this.getAttribute('data-value');
                    const flagSrc = this.getAttribute('data-flag');
                    const langLabel = this.textContent.trim();

                    // Update displayed flag and label
                    const flagIcon = document.getElementById('flagIcon');
                    flagIcon.src = flagSrc;
                    flagIcon.alt = langLabel;
                    document.getElementById('langLabel').textContent = langLabel;

                    // Update URL with lang parameter and reload
                    const url = new URL(window.location);
                    url.searchParams.set('lang', lang);
                    window.location.href = url.toString();
                });
            });
        }