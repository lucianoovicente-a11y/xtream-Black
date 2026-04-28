<?php
/**
 * Sistema de Autenticação e Segurança
 * Gerencia login, logout, sessões e permissões
 */

class Auth {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Realizar login
     */
    public function login($username, $password, $remember = false) {
        try {
            // Verificar rate limiting
            $ip = get_client_ip();
            if (!rate_limit('login_' . $ip, MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_TIME)) {
                return [
                    'success' => false,
                    'message' => 'Muitas tentativas de login. Tente novamente em 15 minutos.'
                ];
            }
            
            // Buscar usuário no banco
            $sql = "SELECT * FROM admin WHERE user = ? AND (admin = '1' OR admin = '2' OR admin = '3')";
            $user = $this->db->fetchOne($sql, [$username]);
            
            if (!$user) {
                logger()->warning("Tentativa de login com usuário inexistente: {$username}", ['ip' => $ip]);
                return [
                    'success' => false,
                    'message' => 'Usuário ou senha inválidos'
                ];
            }
            
            // Verificar se a conta está ativa
            if (isset($user['status']) && $user['status'] == 0) {
                return [
                    'success' => false,
                    'message' => 'Conta desativada. Contate o administrador.'
                ];
            }
            
            // Verificar senha (suporta hash bcrypt e texto puro para legacy)
            $passwordValid = false;
            
            if (password_verify($password, $user['pass'])) {
                $passwordValid = true;
            } elseif ($password === $user['pass']) {
                // Legacy: senha em texto puro - fazer upgrade para hash
                $hashedPassword = hash_password($password);
                $this->db->update('admin', ['pass' => $hashedPassword], 'id = ?', [$user['id']]);
                $passwordValid = true;
            }
            
            if (!$passwordValid) {
                logger()->warning("Senha incorreta para usuário: {$username}", ['ip' => $ip]);
                return [
                    'success' => false,
                    'message' => 'Usuário ou senha inválidos'
                ];
            }
            
            // Login bem-sucedido
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['user'];
            $_SESSION['logged_in'] = true;
            $_SESSION['admin'] = $user['admin'];
            $_SESSION['creditos'] = $user['creditos'] ?? 0;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            // Gerar token CSRF
            generate_csrf_token();
            
            // Log de acesso
            logger()->userActivity($user['id'], 'Login realizado', ['ip' => $ip]);
            
            // Remember me
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                $this->db->insert('user_tokens', [
                    'user_id' => $user['id'],
                    'token' => $token,
                    'expires_at' => $expires,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
            }
            
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['user'],
                    'admin_level' => $user['admin'],
                    'credits' => $user['creditos'] ?? 0
                ]
            ];
            
        } catch (Exception $e) {
            logger()->error("Erro no login: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro ao realizar login. Tente novamente.'
            ];
        }
    }
    
    /**
     * Realizar logout
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logger()->userActivity($_SESSION['user_id'], 'Logout realizado');
        }
        
        // Remover remember me token
        if (isset($_COOKIE['remember_token'])) {
            $this->db->delete('user_tokens', 'token = ?', [$_COOKIE['remember_token']]);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Destruir sessão
        session_destroy();
        
        // Limpar cookies
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        return true;
    }
    
    /**
     * Verificar se usuário está autenticado
     */
    public function check() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar sessão
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            // Verificar remember me
            if (isset($_COOKIE['remember_token'])) {
                return $this->checkRememberToken($_COOKIE['remember_token']);
            }
            return false;
        }
        
        // Verificar timeout da sessão
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            $this->logout();
            return false;
        }
        
        // Atualizar last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Verificar token remember me
     */
    private function checkRememberToken($token) {
        try {
            $tokenData = $this->db->fetchOne(
                "SELECT * FROM user_tokens WHERE token = ? AND expires_at > NOW()",
                [$token]
            );
            
            if ($tokenData) {
                $user = $this->db->fetchOne("SELECT * FROM admin WHERE id = ?", [$tokenData['user_id']]);
                
                if ($user) {
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['user'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['admin'] = $user['admin'];
                    $_SESSION['creditos'] = $user['creditos'] ?? 0;
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    generate_csrf_token();
                    
                    return true;
                }
            }
            
            // Token inválido ou expirado
            setcookie('remember_token', '', time() - 3600, '/');
            return false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verificar se é administrador
     */
    public function isAdmin() {
        return isset($_SESSION['admin']) && $_SESSION['admin'] == '1';
    }
    
    /**
     * Verificar se é revendedor master
     */
    public function isMasterReseller() {
        return isset($_SESSION['admin']) && in_array($_SESSION['admin'], ['1', '2']);
    }
    
    /**
     * Verificar se é revendedor
     */
    public function isReseller() {
        return isset($_SESSION['admin']) && in_array($_SESSION['admin'], ['1', '2', '3']);
    }
    
    /**
     * Obter usuário atual
     */
    public function user() {
        if (!$this->check()) {
            return null;
        }
        
        return $this->db->fetchOne("SELECT * FROM admin WHERE id = ?", [$_SESSION['user_id']]);
    }
    
    /**
     * Obter ID do usuário atual
     */
    public function userId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Obter username do usuário atual
     */
    public function username() {
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Verificar permissão
     */
    public function can($permission) {
        if (!$this->check()) {
            return false;
        }
        
        // Admin tem todas as permissões
        if ($this->isAdmin()) {
            return true;
        }
        
        // Verificar permissões específicas na sessão
        $permissions = $_SESSION['permissions'] ?? [];
        return in_array($permission, $permissions);
    }
    
    /**
     * Forçar login (para APIs)
     */
    public function forceLogin($userId) {
        $user = $this->db->fetchOne("SELECT * FROM admin WHERE id = ?", [$userId]);
        
        if (!$user) {
            return false;
        }
        
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['user'];
        $_SESSION['logged_in'] = true;
        $_SESSION['admin'] = $user['admin'];
        $_SESSION['creditos'] = $user['creditos'] ?? 0;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        generate_csrf_token();
        
        return true;
    }
    
    /**
     * Alterar senha
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            $user = $this->db->fetchOne("SELECT * FROM admin WHERE id = ?", [$userId]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Usuário não encontrado'];
            }
            
            // Verificar senha atual
            $passwordValid = password_verify($oldPassword, $user['pass']) || $oldPassword === $user['pass'];
            
            if (!$passwordValid) {
                return ['success' => false, 'message' => 'Senha atual incorreta'];
            }
            
            // Validar nova senha
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'A nova senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres'];
            }
            
            // Hash da nova senha
            $hashedPassword = hash_password($newPassword);
            
            // Atualizar senha
            $this->db->update('admin', ['pass' => $hashedPassword], 'id = ?', [$userId]);
            
            logger()->userActivity($userId, 'Senha alterada');
            
            return ['success' => true, 'message' => 'Senha alterada com sucesso'];
            
        } catch (Exception $e) {
            logger()->error("Erro ao alterar senha: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao alterar senha'];
        }
    }
    
    /**
     * Recuperar senha (gerar token)
     */
    public function forgotPassword($username) {
        try {
            $user = $this->db->fetchOne("SELECT * FROM admin WHERE user = ?", [$username]);
            
            if (!$user) {
                // Não revelar se o usuário existe ou não
                return ['success' => true, 'message' => 'Se o usuário existir, você receberá instruções para recuperar a senha'];
            }
            
            // Gerar token de recuperação
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $this->db->insert('password_resets', [
                'user_id' => $user['id'],
                'token' => $token,
                'expires_at' => $expires,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Aqui você pode enviar um email com o link de recuperação
            // Por enquanto, apenas logamos
            logger()->info("Token de recuperação de senha gerado para: {$username}", [
                'user_id' => $user['id']
            ]);
            
            return [
                'success' => true,
                'message' => 'Se o usuário existir, você receberá instruções para recuperar a senha',
                'token' => $token // Em produção, não retornar o token
            ];
            
        } catch (Exception $e) {
            logger()->error("Erro ao gerar token de recuperação: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao processar solicitação'];
        }
    }
    
    /**
     * Resetar senha com token
     */
    public function resetPassword($token, $newPassword) {
        try {
            // Verificar token
            $resetData = $this->db->fetchOne(
                "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0",
                [$token]
            );
            
            if (!$resetData) {
                return ['success' => false, 'message' => 'Token inválido ou expirado'];
            }
            
            // Validar nova senha
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return ['success' => false, 'message' => 'A nova senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres'];
            }
            
            // Hash da nova senha
            $hashedPassword = hash_password($newPassword);
            
            // Atualizar senha
            $this->db->update('admin', ['pass' => $hashedPassword], 'id = ?', [$resetData['user_id']]);
            
            // Marcar token como usado
            $this->db->update('password_resets', ['used' => 1], 'id = ?', [$resetData['id']]);
            
            logger()->userActivity($resetData['user_id'], 'Senha recuperada via token');
            
            return ['success' => true, 'message' => 'Senha alterada com sucesso'];
            
        } catch (Exception $e) {
            logger()->error("Erro ao resetar senha: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao resetar senha'];
        }
    }
    
    /**
     * Matar todas as sessões do usuário
     */
    public function killAllSessions($userId) {
        try {
            // Deletar todos os tokens remember me
            $this->db->delete('user_tokens', 'user_id = ?', [$userId]);
            
            // Deletar todos os tokens de recuperação de senha
            $this->db->delete('password_resets', 'user_id = ?', [$userId]);
            
            logger()->userActivity($userId, 'Todas as sessões foram encerradas');
            
            return true;
            
        } catch (Exception $e) {
            logger()->error("Erro ao matar sessões: " . $e->getMessage());
            return false;
        }
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Função global para facilitar o uso
if (!function_exists('auth')) {
    function auth() {
        return Auth::getInstance();
    }
}
