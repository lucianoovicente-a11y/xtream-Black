<?php  
session_start();

include "./functions.php";
include "./autoload.php";

$session = new Sessao();
if ($session->verificar_sessao()) {
    header("Location: ./home");
}

// Carregar configurações do sistema
$config_file = 'config.json';
$config = [];
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
}

$titulo_sistema = $config['title'] ?? 'LUCIANO XTREAM';
$logo_path = $config['logo_path'] ?? './img/logo.png';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($titulo_sistema); ?> - Login</title>
    <link rel="icon" type="image/png" sizes="16x16" href="./images/favicon.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Toastr -->
    <link rel="stylesheet" href="./vendor/toastr/css/toastr.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background Shapes */
        .bg-shape {
            position: absolute;
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
            opacity: 0.1;
        }

        .bg-shape:nth-child(1) {
            width: 300px;
            height: 300px;
            background: #fff;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .bg-shape:nth-child(2) {
            width: 200px;
            height: 200px;
            background: #fff;
            bottom: -50px;
            right: -50px;
            animation-delay: 5s;
        }

        .bg-shape:nth-child(3) {
            width: 150px;
            height: 150px;
            background: #fff;
            top: 50%;
            right: 10%;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(30px, 30px) rotate(90deg); }
            50% { transform: translate(-20px, 50px) rotate(180deg); }
            75% { transform: translate(40px, -30px) rotate(270deg); }
        }

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 10;
            perspective: 1000px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px 40px;
            width: 450px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            transform-style: preserve-3d;
            animation: cardEntrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }

        @keyframes cardEntrance {
            0% {
                opacity: 0;
                transform: translateY(100px) rotateX(30deg);
            }
            100% {
                opacity: 1;
                transform: translateY(0) rotateX(0);
            }
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #667eea);
            background-size: 400% 400%;
            border-radius: 32px;
            z-index: -1;
            animation: gradient 15s ease infinite;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Logo */
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
            transform: translateZ(50px);
        }

        .logo-container img {
            max-width: 200px;
            height: auto;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.2));
            transition: transform 0.3s ease;
        }

        .logo-container img:hover {
            transform: scale(1.05) translateZ(30px);
        }

        /* Title */
        .login-title {
            text-align: center;
            color: #333;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            transform: translateZ(30px);
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-subtitle {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 40px;
            transform: translateZ(20px);
        }

        /* Form Groups */
        .form-group {
            position: relative;
            margin-bottom: 25px;
            transform: translateZ(40px);
        }

        .form-group label {
            display: block;
            color: #555;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px 16px 55px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-control:focus + i {
            color: #764ba2;
            transform: translateY(-50%) scale(1.1);
        }

        /* Remember Me */
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            transform: translateZ(30px);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .remember-me span {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            color: #fff;
            font-size: 18px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            transform: translateZ(50px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-submit:hover {
            transform: translateY(-3px) translateZ(60px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            transform: translateZ(20px);
        }

        .login-footer p {
            color: #888;
            font-size: 13px;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 500px) {
            .login-card {
                width: 90%;
                padding: 40px 25px;
            }

            .login-title {
                font-size: 24px;
            }

            .form-control {
                padding: 14px 15px 14px 50px;
                font-size: 14px;
            }

            .btn-submit {
                font-size: 16px;
                padding: 16px;
            }
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <!-- Background Shapes -->
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>

    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="logo-container">
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($titulo_sistema); ?>" id="system-logo">
            </div>

            <!-- Title -->
            <h1 class="login-title"><?php echo htmlspecialchars($titulo_sistema); ?></h1>
            <p class="login-subtitle">Acesse sua conta para continuar</p>

            <!-- Login Form -->
            <form id="loginForm">
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" class="form-control" placeholder="seu@email.com" required autocomplete="email">
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Senha</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        <i class="fas fa-key"></i>
                    </div>
                </div>

                <!-- Remember & Forgot -->
                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <span>Lembrar de mim</span>
                    </label>
                    <a href="javascript:void(0)" class="forgot-link">Esqueceu a senha?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit" id="btnSubmit">
                    <span id="btnText"><i class="fas fa-sign-in-alt"></i> Entrar</span>
                </button>
            </form>

            <!-- Footer -->
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($titulo_sistema); ?>. Todos os direitos reservados.</p>
                <p>Desenvolvido por <strong>Luciano Vicente - 21971877485</strong></p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="./vendor/global/global.min.js"></script>
    <script src="./vendor/toastr/js/toastr.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function() {
            // Carregar configurações dinâmicas
            $.ajax({
                url: './config.json',
                method: 'GET',
                dataType: 'json',
                success: function(config) {
                    if (config.title) {
                        document.title = config.title + ' - Login';
                        $('.login-title').text(config.title);
                    }
                    if (config.logo_path) {
                        $('#system-logo').attr('src', config.logo_path);
                    }
                },
                error: function() {
                    console.log('Configuração não encontrada, usando padrões');
                }
            });

            // Form Submission
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const btn = $('#btnSubmit');
                const btnText = $('#btnText');
                const formData = $(this).serialize();

                // Disable button
                btn.prop('disabled', true);
                btnText.html('<span class="loading-spinner"></span> Autenticando...');

                $.ajax({
                    url: './actions/login/auth',
                    type: 'POST',
                    dataType: 'json',
                    data: formData,
                    success: function(data) {
                        if (data.status) {
                            btnText.html('<i class="fas fa-check-circle"></i> Autenticado!');
                            
                            toastr.success(data.msg, "Sucesso!", {
                                positionClass: "toast-bottom-right",
                                timeOut: 3000,
                                closeButton: true,
                                progressBar: true
                            });

                            setTimeout(function() {
                                window.location = "home";
                            }, 1500);
                        } else {
                            btn.prop('disabled', false);
                            btnText.html('<i class="fas fa-sign-in-alt"></i> Entrar');
                            
                            toastr.error(data.msg, "Erro de Autenticação!", {
                                positionClass: "toast-bottom-right",
                                timeOut: 4000,
                                closeButton: true,
                                progressBar: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        btn.prop('disabled', false);
                        btnText.html('<i class="fas fa-sign-in-alt"></i> Entrar');
                        
                        toastr.error('Erro de comunicação com o servidor. Tente novamente.', "Erro!", {
                            positionClass: "toast-bottom-right",
                            timeOut: 4000,
                            closeButton: true,
                            progressBar: true
                        });
                    }
                });
            });

            // Input animations
            $('.form-control').on('focus', function() {
                $(this).parent().parent().addClass('focused');
            }).on('blur', function() {
                $(this).parent().parent().removeClass('focused');
            });

            // 3D Card Effect on Mouse Move
            $('.login-card').on('mousemove', function(e) {
                const card = $(this);
                const rect = card[0].getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 10;
                const rotateY = (centerX - x) / 10;
                
                card.css({
                    transform: `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`
                });
            }).on('mouseleave', function() {
                $(this).css({
                    transform: 'perspective(1000px) rotateX(0) rotateY(0) scale(1)'
                });
            });
        });
    </script>
</body>
</html>
