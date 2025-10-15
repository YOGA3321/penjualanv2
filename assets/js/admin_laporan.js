document.addEventListener('DOMContentLoaded', () => {
    // Cek apakah elemen canvas ada di halaman
    const ctx = document.getElementById('salesChart');

    if (ctx) {
        // Data dummy untuk grafik
        const labels = ['09 Okt', '10 Okt', '11 Okt', '12 Okt', '13 Okt', '14 Okt', '15 Okt'];
        const salesData = [850000, 1100000, 950000, 1350000, 1200000, 1500000, 1250000];

        new Chart(ctx, {
            type: 'line', // Tipe grafik: garis
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: salesData,
                    fill: true,
                    backgroundColor: 'rgba(13, 110, 253, 0.1)', // Warna area di bawah garis
                    borderColor: 'rgba(13, 110, 253, 1)', // Warna garis
                    tension: 0.4, // Membuat garis lebih melengkung
                    pointBackgroundColor: 'rgba(13, 110, 253, 1)',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(13, 110, 253, 1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            // Format angka menjadi 'Rp xxx'
                            callback: function(value, index, values) {
                                return 'Rp ' + (value / 1000) + 'k';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Sembunyikan label dataset di atas grafik
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            }
        });
    }
});