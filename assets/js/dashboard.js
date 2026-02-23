const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const btnToggle = document.getElementById('btnToggle');

btnToggle.addEventListener('click', () => {
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
});
overlay.addEventListener('click', () => {
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
});
