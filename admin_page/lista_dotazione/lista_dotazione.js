window.addEventListener('DOMContentLoaded', () => {
    document.getElementById("filterInput").addEventListener("keyup", function () {
        const filterValue = this.value.toLowerCase();
        const rows = document.querySelectorAll(".lista-dotazioni table tbody tr");

        rows.forEach(row => {
            const codice = row.cells[0]?.textContent.toLowerCase() || "";
            const nome = row.cells[1]?.textContent.toLowerCase() || "";
            const match = codice.includes(filterValue) || nome.includes(filterValue);
            row.style.display = match ? "" : "none";
        });
    }); 
});