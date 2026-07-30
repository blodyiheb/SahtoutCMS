        // JavaScript to keep In-Game tab active when pagination or filters are used
        document.querySelectorAll('#ingame-tab .pagination a.page-link, #ingame-tab form.search-form').forEach(element => {
            element.addEventListener('click', function(event) {
                document.querySelector('.nav-tabs .nav-link[href="#website-tab"]').classList.remove('active');
                document.querySelector('#website-tab').classList.remove('show', 'active');
                document.querySelector('.nav-tabs .nav-link[href="#ingame-tab"]').classList.add('active');
                document.querySelector('#ingame-tab').classList.add('show', 'active');
            });
        });
        // JavaScript to show/hide ban fields or GM level fields based on ban_action selection
        document.querySelectorAll('select[name="ban_action"]').forEach(select => {
            select.addEventListener('change', function() {
                const modalId = this.id.split('-')[1];
                const banFields = document.getElementById('banFields-' + modalId);
                const gmFields = document.getElementById('gmFields-' + modalId);
                if (this.value === 'ban') {
                    banFields.style.display = 'block';
                    gmFields.style.display = 'none';
                } else if (this.value === 'unban') {
                    banFields.style.display = 'none';
                    gmFields.style.display = 'none';
                } else {
                    banFields.style.display = 'none';
                    gmFields.style.display = 'block';
                }
            });
        });