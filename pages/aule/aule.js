window.addEventListener('DOMContentLoaded', () => {
    document.getElementById("filterInput").addEventListener("keyup", function () {
        const filterValue = this.value.toLowerCase();
        const rows = document.querySelectorAll(".lista-dotazioni table tbody tr");
        rows.forEach(row => {
            const codice = row.cells[0]?.textContent.toLowerCase() || "";
            const descrizione = row.cells[2]?.textContent.toLowerCase() || "";
            const match = codice.includes(filterValue) || descrizione.includes(filterValue);
            row.style.display = match ? "" : "none";
        });
    });
});