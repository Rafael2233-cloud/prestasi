document.addEventListener('DOMContentLoaded', function() {
    // Check local storage for sidebar state
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        document.body.classList.add('sidebar-collapsed');
    }
});

function toggleSidebar() {
    document.body.classList.toggle('sidebar-collapsed');
    
    // Save state to local storage
    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
}
