/**
 * SCRIPT-GOAH.JS - INTEGRATED VERSION 2026
 */

// --- 1. FUNGSI NAVIGASI (AJAX) ---
function loadPage(page) {
    const contentDiv = document.getElementById("content-container");
    const loader = document.getElementById("kandaga-loader"); // Ambil elemen loader
    
    if (!contentDiv) return;

    // A. TAMPILKAN LOADER
    if (loader) loader.style.display = "flex";

    const url = page + (page.includes('?') ? '&' : '?') + 't=' + new Date().getTime();

    fetch(url)
        .then(response => response.text())
        .then(html => {
            contentDiv.innerHTML = html;
            
            // Re-run scripts di dalam file PHP yang di-load
            const scripts = contentDiv.querySelectorAll("script");
            scripts.forEach(oldScript => {
                const newScript = document.createElement("script");
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                } else {
                    newScript.textContent = oldScript.textContent;
                }
                document.body.appendChild(newScript).parentNode.removeChild(newScript);
            });

            // B. SEMBUNYIKAN LOADER SETELAH SELESAI
            if (loader) {
                setTimeout(() => { loader.style.display = "none"; }, 300); // Beri jeda sedikit agar smooth
            }
        })
        .catch(err => {
            console.error('Error loading page:', err);
            // SEMBUNYIKAN LOADER JIKA ERROR
            if (loader) loader.style.display = "none";
        });
}

// --- 2. FUNGSI DATATABLES ---
function initKatalogTable() {
    const tableSelector = '#katalog-table';
    if ($(tableSelector).length) {
        if ($.fn.DataTable.isDataTable(tableSelector)) {
            $(tableSelector).DataTable().destroy();
        }
        $(tableSelector).DataTable({
            "pageLength": 20,
            "order": [], // Sesuai urutan DESC dari PHP
            "responsive": true,
            "dom": '<"d-flex justify-content-between mb-3"lf>rt<"d-flex justify-content-between mt-3"ip>'
        });
    }
}

// --- 3. FUNGSI TINYMCE ---
function initTinyMCE(selector) {
    if (typeof tinymce !== 'undefined') {
        tinymce.remove(selector);
        tinymce.init({
            selector: selector,
            height: 350,
            menubar: false,
            plugins: 'lists link code table wordcount',
            toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | code',
            setup: function (editor) {
                editor.on('change', function () { editor.save(); });
            }
        });
    }
}

// --- 4. EVENT DELEGATION (SOLUSI TOMBOL EDIT TIDAK JALAN) ---
document.addEventListener("click", function (e) {
    // Cari elemen terdekat yang memiliki class tombol aksi kita
    const target = e.target.closest(".edit-data-naskah, .lihat-data-naskah, #tambah-data-btn");

    if (target) {
        e.preventDefault();
        const page = target.getAttribute("data-page") || target.getAttribute("href");
        
        if (page && page !== "#") {
            console.log("Navigasi ke:", page);
            loadPage(page);
        }
    }
});

console.log("Goah Engine: System Ready");
