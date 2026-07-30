        // Function to toggle action fields based on selection
        function toggleActionFields(selectElement) {
            const modalId = selectElement.id.split('-')[1];
            const goldFields = document.getElementById('goldFields-' + modalId);
            const levelFields = document.getElementById('levelFields-' + modalId);
            const teleportFields = document.getElementById('teleportFields-' + modalId);
            const teleportDirectlyFields = document.getElementById('teleportDirectlyFields-' + modalId);
            
            // Hide all first
            goldFields.style.display = 'none';
            levelFields.style.display = 'none';
            teleportFields.style.display = 'none';
            teleportDirectlyFields.style.display = 'none';
            goldFields.querySelector('input').required = false;
            levelFields.querySelector('input').required = false;
            teleportFields.querySelectorAll('input').forEach(i => i.required = false);
            
            // Show only the selected one
            if (selectElement.value === 'add_gold') {
                goldFields.style.display = 'block';
                goldFields.querySelector('input').required = true;
            } else if (selectElement.value === 'change_level') {
                levelFields.style.display = 'block';
                levelFields.querySelector('input').required = true;
            } else if (selectElement.value === 'teleport') {
                teleportFields.style.display = 'block';
                teleportFields.querySelectorAll('input').forEach(i => i.required = true);
            } else if (selectElement.value === 'teleport_directly') {
                teleportDirectlyFields.style.display = 'block';
            }
        }

        // Initialize action fields when modal is shown
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = this.getAttribute('data-bs-target').replace('#', '');
                const selectElement = document.querySelector('#' + modalId + ' select[name="char_action"]');
                selectElement.dispatchEvent(new Event('change'));
            });
        });

        // Initialize all modals on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select[name="char_action"]').forEach(select => {
                toggleActionFields(select);
            });
        });