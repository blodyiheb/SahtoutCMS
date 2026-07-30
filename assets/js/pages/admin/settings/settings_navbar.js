document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.querySelector('.settings-nav .mobile-toggle');
    const navTabs = document.querySelector('.settings-nav-tabs');
    
    toggleButton.addEventListener('click', function() {
        navTabs.classList.toggle('active');
        toggleButton.classList.toggle('active');
    });
});