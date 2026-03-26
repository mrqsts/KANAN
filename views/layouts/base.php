<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'KANAN' ?> - Mi Salud Segura</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2d6a4f; /* Verde bosque tranquilo */
            --secondary: #1a759f; /* Azul océano suave */
            --accent: #52b788; /* Verde agua */
            --bg-gradient: linear-gradient(135deg, #e0f2f1 0%, #e1f5fe 100%);
            --card-bg: rgba(255, 255, 255, 0.9);
            --text: #264653;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            background-attachment: fixed;
            color: var(--text);
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .navbar .logo {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: 1px;
        }

        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
            width: 100%;
        }

        .card {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.07);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        h1, h2, h3 { color: var(--secondary); margin-top: 0; }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #1b4332; transform: translateY(-2px); }
        
        .btn-secondary { background: var(--secondary); color: white; }
        .btn-secondary:hover { background: #184e77; transform: translateY(-2px); }

        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; }
        
        input, select, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 1rem;
            background: rgba(255,255,255,0.8);
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(82, 183, 136, 0.2);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 5px;
        }

        footer {
            margin-top: auto;
            text-align: center;
            padding: 2rem;
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php?route=dashboard" class="logo">KANAN</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="index.php?route=logout" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">Cerrar Sesión</a>
        <?php endif; ?>
    </nav>

    <div class="container">
        <?= $content ?>
    </div>

    <footer>
        &copy; <?= date('Y') ?> KANAN - Tu Bitácora de Salud Privada
    </footer>
</body>
</html>
