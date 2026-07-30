    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.querySelector('.mobile-toggle');
        const menu = document.querySelector('.admin-sidebar-menu');
        
        toggleButton.addEventListener('click', function() {
            menu.classList.toggle('active');
            toggleButton.classList.toggle('active');
        });
    });