<?php
session_start();
if (isset($_SESSION['logged_in_fxtream']) && $_SESSION['logged_in_fxtream'] === true) {
    header('Location: dashboard.php');
    exit;
}

$config_file = 'config.json';
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
} else {
    $config = [
        'title' => 'FÊNIX PLAY TV',
        'logo_path' => './img/logo.png'
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($config['title']); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($config['logo_path']); ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@300;400;500;600;700;800&family=Orbitron:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-dark: #0a0a12;
            --bg-card: #12121e;
            --bg-card-hover: #1a1a2e;
            --accent: #ff6b35;
            --accent-glow: rgba(255, 107, 53, 0.5);
            --accent-light: rgba(255, 107, 53, 0.15);
            --accent-secondary: #f7c35f;
            --text: #ffffff;
            --text-muted: #a0a0b0;
            --border: rgba(255, 107, 53, 0.3);
            --shadow: rgba(0, 0, 0, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: 'Exo 2', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text);
            overflow: hidden;
            font-size: 15px;
        }

        /* Background com gradiente e padrões */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse at 20% 80%, rgba(255, 107, 53, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(247, 195, 95, 0.1) 0%, transparent 50%),
                linear-gradient(180deg, #0a0a12 0%, #0f0f1a 100%);
            pointer-events: none;
            z-index: 0;
        }

        /* Grade decorativa */
        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255, 107, 53, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 107, 53, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .login-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            width: 100%;
            max-width: 400px;
            padding: 24px;
        }

        .login-box {
            background: linear-gradient(145deg, #14141f, #0e0e18);
            padding: 44px 38px;
            border-radius: 24px;
            box-shadow: 
                0 25px 50px -12px var(--shadow),
                0 0 0 1px var(--border),
                0 0 40px rgba(255, 107, 53, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            width: 100%;
            text-align: center;
            position: relative;
        }

        /* Borda luminosa */
        .login-box::before {
            content: "";
            position: absolute;
            top: -1px;
            left: -1px;
            right: -1px;
            bottom: -1px;
            border-radius: 25px;
            background: linear-gradient(135deg, var(--accent), var(--accent-secondary), var(--accent));
            z-index: -1;
            opacity: 0.3;
            animation: borderGlow 3s ease-in-out infinite;
        }

        @keyframes borderGlow {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }

        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 24px;
            border-radius: 20px;
            box-shadow: 
                0 8px 25px rgba(255, 107, 53, 0.4),
                0 0 30px rgba(255, 107, 53, 0.2);
            border: 2px solid var(--accent);
            animation: logoPulse 2s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4), 0 0 30px rgba(255, 107, 53, 0.2); }
            50% { box-shadow: 0 8px 30px rgba(255, 107, 53, 0.6), 0 0 50px rgba(255, 107, 53, 0.3); }
        }

        .login-box h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
            background: linear-gradient(135deg, var(--accent), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 32px;
        }

        .input-group {
            position: relative;
            margin-bottom: 24px;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent);
            font-size: 18px;
            transition: all 0.3s;
            z-index: 2;
        }

        .input-group input {
            width: 100%;
            padding: 16px 16px 16px 52px;
            font-size: 15px;
            font-family: 'Exo 2', sans-serif;
            font-weight: 500;
            color: var(--text);
            background: linear-gradient(145deg, #0c0c16, #0a0a12);
            border: 2px solid rgba(255, 107, 53, 0.2);
            border-radius: 14px;
            outline: none;
            transition: all 0.3s;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .input-group input:focus {
            border-color: var(--accent);
            box-shadow: 
                inset 0 2px 4px rgba(0, 0, 0, 0.3),
                0 0 20px rgba(255, 107, 53, 0.3);
        }

        .input-group input:focus + i {
            color: var(--accent-secondary);
            text-shadow: 0 0 15px var(--accent);
        }

        .input-group input::placeholder {
            color: var(--text-muted);
        }

        /* Botão 3D */
        .login-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(180deg, var(--accent) 0%, #e55a2b 100%);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 
                0 6px 0 #b8441f,
                0 8px 20px rgba(255, 107, 53, 0.4);
            position: relative;
            top: 0;
        }

        .login-button:hover {
            background: linear-gradient(180deg, #ff7a45 0%, var(--accent) 100%);
            transform: translateY(-2px);
            box-shadow: 
                0 8px 0 #b8441f,
                0 12px 30px rgba(255, 107, 53, 0.5);
        }

        .login-button:active {
            transform: translateY(4px);
            box-shadow: 
                0 2px 0 #b8441f,
                0 4px 10px rgba(255, 107, 53, 0.3);
        }

        .login-button .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .version-text {
            margin-top: 28px;
            font-family: 'Orbitron', sans-serif;
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: 2px;
        }

        .signature {
            margin-top: 14px;
            font-size: 14px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .signature strong {
            color: var(--accent);
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div id="particles-js"></div>

    <div class="login-container">
        <div class="login-box">
            <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" alt="Logo" class="logo">
            <h2>Acesso Sistema</h2>
            <form id="login-form">
                <div class="input-group">
                    <input type="text" id="username" name="username" required placeholder="Usuário">
                    <i class="fas fa-user"></i>
                </div>
                <div class="input-group">
                    <input type="password" id="password" name="password" required placeholder="Senha">
                    <i class="fas fa-lock"></i>
                </div>
                <button type="submit" class="login-button">
                    <span class="button-text"><i class="fas fa-sign-in-alt"></i> Entrar</span>
                    <div class="spinner"></div>
                </button>
            </form>
            <p class="version-text">FÊNIX PLAY TV v5.2.34</p>
            <p class="signature">
                <i class="fas fa-copyright"></i> Luciano Vicente - <strong>21971877485</strong>
            </p>
        </div>
    </div>

    <script>
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        document.getElementById('particles-js').appendChild(canvas);
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        let particlesArray;
        let mouse = { x: null, y: null, radius: 120 };

        window.addEventListener('mousemove', function(event) {
            mouse.x = event.x;
            mouse.y = event.y;
        });

        class Particle {
            constructor(x, y, directionX, directionY, size, color) {
                this.x = x; this.y = y; this.directionX = directionX;
                this.directionY = directionY; this.size = size; this.color = color;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2, false);
                ctx.fillStyle = this.color;
                ctx.fill();
            }
            update() {
                if (this.x > canvas.width || this.x < 0) this.directionX = -this.directionX;
                if (this.y > canvas.height || this.y < 0) this.directionY = -this.directionY;
                
                let dx = mouse.x - this.x;
                let dy = mouse.y - this.y;
                let distance = Math.sqrt(dx*dx + dy*dy);
                if (distance < mouse.radius + this.size) {
                    if (mouse.x < this.x && this.x < canvas.width - this.size * 5) this.x += 3;
                    if (mouse.x > this.x && this.x > this.size * 5) this.x -= 3;
                    if (mouse.y < this.y && this.y < canvas.height - this.size * 5) this.y += 3;
                    if (mouse.y > this.y && this.y > this.size * 5) this.y -= 3;
                }
                
                this.x += this.directionX;
                this.y += this.directionY;
                this.draw();
            }
        }

        function init() {
            particlesArray = [];
            let numberOfParticles = 70;
            for (let i = 0; i < numberOfParticles; i++) {
                let size = Math.random() * 2 + 1;
                let x = Math.random() * canvas.width;
                let y = Math.random() * canvas.height;
                let directionX = (Math.random() * 1.2) - 0.6;
                let directionY = (Math.random() * 1.2) - 0.6;
                let color = Math.random() > 0.6 ? 'rgba(255, 107, 53, 0.6)' : 'rgba(247, 195, 95, 0.5)';
                particlesArray.push(new Particle(x, y, directionX, directionY, size, color));
            }
        }

        function animate() {
            requestAnimationFrame(animate);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particlesArray.length; i++) {
                particlesArray[i].update();
            }
        }

        init();
        animate();

        window.addEventListener('resize', function() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            init();
        });
    </script>
    
    <script>
        document.getElementById('login-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const button = this.querySelector('.login-button');
            const buttonText = button.querySelector('.button-text');
            const spinner = button.querySelector('.spinner');

            buttonText.style.display = 'none';
            spinner.style.display = 'block';

            const formData = new FormData(this);

            fetch('api/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.icon === 'success') {
                    Swal.fire({
                        title: data.title,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#12121e',
                        color: '#ffffff',
                        toast: true,
                        position: 'top'
                    }).then(() => {
                        window.location.href = data.url;
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: data.title,
                        icon: 'error',
                        background: '#12121e',
                        color: '#ffffff',
                        toast: true,
                        position: 'top'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Erro de Conexão',
                    text: 'Não foi possível conectar ao servidor.',
                    icon: 'error',
                    background: '#12121e',
                    color: '#ffffff'
                });
            })
            .finally(() => {
                buttonText.style.display = 'block';
                spinner.style.display = 'none';
            });
        });
    </script>

</body>
</html>