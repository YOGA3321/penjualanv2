<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8f9fa; }
        .error-card { text-align: center; padding: 40px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 500px; width: 90%; }
        .icon-box { color: #6f42c1; font-size: 5rem; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-box"><i class="fas fa-server"></i></div>
        <h2 class="fw-bold mb-3">Gangguan Server</h2>
        <p class="text-muted mb-4">Maaf, terjadi kesalahan pada server kami. Silakan coba beberapa saat lagi.</p>
        <a href="javascript:location.reload()" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="fas fa-sync-alt me-2"></i>Refresh</a>
        <a href="../" class="btn btn-outline-secondary rounded-pill px-4 fw-bold ms-2">Beranda</a>
    </div>
</body>
</html>
