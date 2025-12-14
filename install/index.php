<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Aplikasi Penjualan</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .install-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }
        .install-card h2 {
            text-align: center;
            color: #333;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box; 
        }
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .mode-switch {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .mode-option {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }
        .mode-option.active {
            background-color: #e3f2fd;
            border-color: #007bff;
            color: #007bff;
            font-weight: bold;
        }
        .alert {
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            display: none;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="install-card">
    <h2>Instalasi Sistem</h2>
    
    <?php if(isset($_GET['error'])): ?>
    <div class="alert alert-error" style="display:block">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
    <?php endif; ?>

    <form action="process.php" method="POST">
        <div class="form-group">
            <label>Mode Instalasi</label>
            <div class="mode-switch">
                <div class="mode-option active" onclick="setMode('manual')">Manual</div>
                <div class="mode-option" onclick="setMode('auto')">Otomatis</div>
            </div>
            <input type="hidden" name="mode" id="modeInput" value="manual">
            <small id="modeHelp" style="color: #666; font-size: 0.85rem;">
                Gunakan database yang sudah ada.
            </small>
        </div>

        <div class="form-group">
            <label>Database Host</label>
            <input type="text" name="db_host" class="form-control" value="localhost" required>
        </div>

        <div class="form-group">
            <label>Database User</label>
            <input type="text" name="db_user" class="form-control" value="root" required>
        </div>

        <div class="form-group">
            <label>Database Password</label>
            <input type="password" name="db_pass" class="form-control" placeholder="Kosongkan jika tidak ada">
        </div>

        <div class="form-group">
            <label>Nama Database</label>
            <input type="text" name="db_name" class="form-control" value="penjualan2" required>
        </div>

        <button type="submit" class="btn-primary">Install Aplikasi</button>
    </form>
</div>

<script>
    function setMode(mode) {
        document.getElementById('modeInput').value = mode;
        const options = document.querySelectorAll('.mode-option');
        const helpText = document.getElementById('modeHelp');
        
        options.forEach(opt => opt.classList.remove('active'));
        
        if (mode === 'manual') {
            options[0].classList.add('active');
            helpText.innerText = "Gunakan database yang sudah ada (struktur tabel harus sudah siap atau kosong).";
        } else {
            options[1].classList.add('active');
            helpText.innerText = "Sistem akan membuat database baru dan mengimport struktur tabel.";
        }
    }
</script>

</body>
</html>
