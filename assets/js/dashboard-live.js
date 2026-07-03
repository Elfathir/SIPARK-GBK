document.addEventListener("DOMContentLoaded", () => {

    loadDashboard();

    // refresh setiap 15 detik
    setInterval(loadDashboard, 15000);

});

async function loadDashboard(){

    try {
        const response = await fetch("../dashboard/dashboard_data.php");
        const data = await response.json();
        updateDashboard(data);
        refreshCharts(data);
    } catch(error){
        console.error("Dashboard gagal diperbarui :", error);
    }

}

function updateDashboard(data){
    if(!document.getElementById("totalKendaraan") ||
    !document.getElementById("totalArea") ||
    !document.getElementById("totalUsers")){
        return;
    }
    // Total kendaraan
    document.getElementById("totalKendaraan").textContent =
        data.kendaraan;

    document.getElementById("detailKendaraan").innerHTML =
        `🚗 ${data.mobil} Mobil | 🏍 ${data.motor} Motor`;

    // Area
    document.getElementById("totalArea").textContent =
        data.area;

    // Petugas
    document.getElementById("totalPetugas").textContent =
        data.petugas;

    // Users
    document.getElementById("totalUsers").textContent =
        data.users;

    // Transaksi
    document.getElementById("totalTransaksi").textContent =
        data.transaksi;

    // Pendapatan
    document.getElementById("totalPendapatan").textContent =
        "Rp " + Number(data.pendapatan).toLocaleString("id-ID");
}