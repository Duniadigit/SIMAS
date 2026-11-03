// Menu sidebar mobile (tanpa library tambahan).
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('backdrop');
const btn = document.getElementById('menu-btn');

if (sidebar && btn && backdrop) {
    const toggle = (open) => {
        sidebar.classList.toggle('hidden', !open);
        sidebar.classList.toggle('flex', open);
        backdrop.classList.toggle('hidden', !open);
    };
    btn.addEventListener('click', () => toggle(sidebar.classList.contains('hidden')));
    backdrop.addEventListener('click', () => toggle(false));
}

// Cegah klik ganda pada form (mis. ACC dua kali).
document.querySelectorAll('form[data-once]').forEach((form) => {
    form.addEventListener('submit', () => {
        form.querySelectorAll('button[type=submit], button:not([type])').forEach((b) => (b.disabled = true));
    });
});

// Konfirmasi untuk aksi penting.
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (e) => {
        if (!window.confirm(form.dataset.confirm)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });
});
