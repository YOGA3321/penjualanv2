<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8f9fa; }
        .error-card { text-align: center; padding: 40px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 500px; width: 90%; }
        .icon-box { color: #dc3545; font-size: 5rem; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-box"><i class="fas fa-ban"></i></div>
        <h2 class="fw-bold mb-3">Akses Ditolak</h2>
        <p class="text-muted mb-4">Maaf, Anda tidak memiliki izin untuk mengakses halaman atau direktori ini.</p>
        <a href="javascript:history.back()" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
        <a href="../" class="btn btn-outline-secondary rounded-pill px-4 fw-bold ms-2">Beranda</a>
    </div>
</body>
</html>
