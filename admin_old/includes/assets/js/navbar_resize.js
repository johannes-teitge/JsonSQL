// Variablen für das Resizing
let isResizing = false;
let lastDownX = 0;
const sidebar = document.getElementById('sidebar');
const resizer = document.getElementById('resizer');
const content = document.getElementById('content');

// Wenn der Benutzer die Trennlinie klickt und zieht
resizer.addEventListener('mousedown', (e) => {
    isResizing = true;
    lastDownX = e.clientX;
    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('mouseup', () => {
        isResizing = false;
        document.removeEventListener('mousemove', handleMouseMove);
    });
});

// Wenn der Benutzer die Maus bewegt, während er zieht
function handleMouseMove(e) {
    if (!isResizing) return;
    
    const offsetRight = e.clientX - lastDownX;
    let newWidth = sidebar.offsetWidth + offsetRight;

    // Minimale und maximale Breite der Sidebar festlegen
    if (newWidth > 150 && newWidth < 500) {
        sidebar.style.width = newWidth + 'px';
        content.style.marginLeft = newWidth + 'px'; // Inhalt verschieben
        lastDownX = e.clientX;
    }
}
