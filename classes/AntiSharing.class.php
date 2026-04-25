<?php
/**
 * AntiSharing.class.php
 * 
 * Sistema Inteligente de Anti-Compartilhamento
 * Detecta e bloqueia compartilhamento de contas através de:
 * - Fingerprint de dispositivo
 * - Análise geográfica (GeoIP)
 * - Detecção de VPN/Proxy
 * - Padrões de uso anômalos
 */

class AntiSharing {
    private $db;
    private $logger;
    private $connectionManager;

    // Configurações de sensibilidade
    private $config = [
        'max_locations_per_hour' => 3,      // Máximo de locais diferentes por hora
        'max_devices_per_day' => 5,         // Máximo de dispositivos diferentes por dia
        'vpn_detection_enabled' => true,    // Ativar detecção de VPN
        'geo_blocking_enabled' => true,     // Ativar bloqueio geográfico
        'fingerprint_strict' => true,       // Modo estrito de fingerprint
        'allowed_countries' => ['BR'],      // Países permitidos (opcional)
        'block_vpn_immediately' => false    // Bloquear imediatamente ou apenas alertar
    ];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger('anti_sharing');
        $this->connectionManager = new ConnectionManager();
    }

    /**
     * Valida uma nova tentativa de conexão
     * Retorna: ['allowed' => bool, 'reason' => string, 'risk_score' => int]
     */
    public function validateConnection($userId, $ip, $userAgent) {
        $user = $this->db->select('users', '*', ['id' => $userId], 'fetch');
        if (!$user) return ['allowed' => false, 'reason' => 'Usuário não encontrado', 'risk_score' => 100];

        $riskScore = 0;
        $alerts = [];

        // 1. Gerar fingerprint do dispositivo
        $fingerprint = $this->generateFingerprint($userAgent, $ip);
        
        // 2. Verificar histórico de dispositivos
        $deviceCheck = $this->checkDeviceHistory($userId, $fingerprint);
        if ($deviceCheck['is_new']) {
            $riskScore += 15;
            $alerts[] = 'Novo dispositivo detectado';
        }

        // 3. Análise GeoIP
        $geoInfo = $this->getGeoLocation($ip);
        $geoCheck = $this->checkGeoAnomalies($userId, $geoInfo);
        if ($geoCheck['suspicious']) {
            $riskScore += $geoCheck['risk_level'] * 10;
            $alerts[] = $geoCheck['reason'];
        }

        // 4. Detecção de VPN/Proxy
        if ($this->config['vpn_detection_enabled']) {
            $vpnCheck = $this->detectVPN($ip);
            if ($vpnCheck['is_vpn']) {
                $riskScore += 40;
                $alerts[] = 'VPN/Proxy detectado';
                
                if ($this->config['block_vpn_immediately']) {
                    $this->logViolation($userId, $ip, 'VPN_DETECTED', $alerts);
                    return ['allowed' => false, 'reason' => 'Uso de VPN não permitido', 'risk_score' => $riskScore];
                }
            }
        }

        // 5. Verificar países permitidos
        if ($this->config['geo_blocking_enabled'] && !empty($this->config['allowed_countries'])) {
            if (!in_array($geoInfo['country_code'], $this->config['allowed_countries'])) {
                $riskScore += 50;
                $alerts[] = 'País não permitido: ' . $geoInfo['country'];
                
                $this->logViolation($userId, $ip, 'COUNTRY_BLOCKED', $alerts);
                return ['allowed' => false, 'reason' => 'Acesso não permitido do seu país', 'risk_score' => $riskScore];
            }
        }

        // 6. Analisar padrão de velocidade (impossible travel)
        $travelCheck = $this->checkImpossibleTravel($userId, $geoInfo);
        if ($travelCheck['impossible']) {
            $riskScore += 60;
            $alerts[] = 'Viagem impossível detectada';
        }

        // Decisão final baseada no risk score
        $allowed = $riskScore < 70; // Threshold de 70 pontos
        
        if (!$allowed) {
            $this->logViolation($userId, $ip, 'HIGH_RISK', $alerts);
        }

        // Registrar tentativa
        $this->recordConnectionAttempt($userId, $ip, $fingerprint, $geoInfo, $riskScore, $allowed);

        return [
            'allowed' => $allowed,
            'reason' => $allowed ? 'Autorizado' : implode('. ', $alerts),
            'risk_score' => $riskScore,
            'geo_info' => $geoInfo,
            'fingerprint' => $fingerprint
        ];
    }

    /**
     * Gera fingerprint único do dispositivo baseado em User-Agent e características
     */
    private function generateFingerprint($userAgent, $ip) {
        // Extrair informações do User-Agent
        $parsed = $this->parseUserAgent($userAgent);
        
        // Criar hash único
        $components = [
            $parsed['browser'],
            $parsed['version'],
            $parsed['os'],
            $parsed['platform'],
            substr($ip, 0, strrpos($ip, '.')) // Primeiros 3 octetos do IP (rede)
        ];
        
        return hash('sha256', implode('|', $components));
    }

    /**
     * Verifica histórico de dispositivos do usuário
     */
    private function checkDeviceHistory($userId, $fingerprint) {
        // Buscar dispositivos conhecidos nas últimas 24h
        $knownDevices = $this->db->select('device_fingerprints', '*', [
            'user_id' => $userId,
            'last_seen[>]' => date('Y-m-d H:i:s', strtotime('-24 hours'))
        ], 'all');

        $exists = false;
        foreach ($knownDevices as $device) {
            if ($device['fingerprint'] === $fingerprint) {
                $exists = true;
                // Atualizar last_seen
                $this->db->update('device_fingerprints', ['last_seen' => date('Y-m-d H:i:s')], ['id' => $device['id']]);
                break;
            }
        }

        if (!$exists) {
            // Contar novos dispositivos hoje
            $newToday = $this->db->count('device_fingerprints', [
                'user_id' => $userId,
                'first_seen[>]' => date('Y-m-d H:i:s', strtotime('-24 hours'))
            ]);

            if ($newToday >= $this->config['max_devices_per_day']) {
                return [
                    'is_new' => true,
                    'exceeded_limit' => true,
                    'count' => $newToday
                ];
            }

            // Registrar novo dispositivo
            $this->db->insert('device_fingerprints', [
                'user_id' => $userId,
                'fingerprint' => $fingerprint,
                'first_seen' => date('Y-m-d H:i:s'),
                'last_seen' => date('Y-m-d H:i:s')
            ]);
        }

        return ['is_new' => !$exists, 'exceeded_limit' => false];
    }

    /**
     * Obtém localização via GeoIP (simulado - usar MaxMind em produção)
     */
    private function getGeoLocation($ip) {
        // Em produção: usar GeoIP2 da MaxMind
        // $reader = new \GeoIp2\Database\Reader('/path/to/GeoLite2-City.mmdb');
        // $record = $reader->city($ip);
        
        // Simulação com API externa ou banco local
        $geoData = $this->db->select('geoip_cache', '*', ['ip' => $ip], 'fetch');
        
        if (!$geoData || strtotime($geoData['expires_at']) < time()) {
            // Consultar API (ex: ipapi.co, ipinfo.io)
            $apiResponse = @file_get_contents("http://ip-api.com/json/{$ip}");
            $data = json_decode($apiResponse, true);
            
            if ($data && $data['status'] === 'success') {
                $geoData = [
                    'ip' => $ip,
                    'country' => $data['country'],
                    'country_code' => $data['countryCode'],
                    'region' => $data['regionName'],
                    'city' => $data['city'],
                    'lat' => $data['lat'],
                    'lon' => $data['lon'],
                    'isp' => $data['isp'],
                    'cached_at' => date('Y-m-d H:i:s'),
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
                ];
                
                // Cache no banco
                if ($geoData['id'] ?? false) {
                    $this->db->update('geoip_cache', $geoData, ['id' => $geoData['id']]);
                } else {
                    $this->db->insert('geoip_cache', $geoData);
                }
            } else {
                $geoData = ['country_code' => 'XX', 'country' => 'Unknown', 'lat' => 0, 'lon' => 0];
            }
        }

        return $geoData;
    }

    /**
     * Verifica anomalias geográficas
     */
    private function checkGeoAnomalies($userId, $geoInfo) {
        // Buscar últimas localizações nas últimas 2 horas
        $recentLocations = $this->db->query("
            SELECT DISTINCT country_code, city, lat, lon, created_at 
            FROM connection_attempts 
            WHERE user_id = ? AND created_at > ?
            ORDER BY created_at DESC
            LIMIT 10
        ", [$userId, date('Y-m-d H:i:s', strtotime('-2 hours'))])->fetchAll(PDO::FETCH_ASSOC);

        $uniqueLocations = array_unique(array_column($recentLocations, 'city'));
        
        if (count($uniqueLocations) >= $this->config['max_locations_per_hour']) {
            return [
                'suspicious' => true,
                'reason' => 'Múltiplas localizações em curto período',
                'risk_level' => count($uniqueLocations)
            ];
        }

        return ['suspicious' => false, 'risk_level' => 0];
    }

    /**
     * Detecta se IP é de VPN/Proxy
     */
    private function detectVPN($ip) {
        // Em produção: usar API como IPQualityScore, MaxMind, ou lista de IPs conhecidos
        // Simulação: verificar ranges comuns de VPN
        
        $vpnRanges = [
            '185.220.', // Tor exit nodes comum
            '23.129.',  // VPN comum
            // Adicionar mais ranges...
        ];

        foreach ($vpnRanges as $range) {
            if (strpos($ip, $range) === 0) {
                return ['is_vpn' => true, 'type' => 'Known VPN Range'];
            }
        }

        // Verificar portas comuns de proxy
        // (requer socket scan - pode ser pesado)
        
        return ['is_vpn' => false, 'type' => null];
    }

    /**
     * Detecta "impossible travel" (viagens fisicamente impossíveis)
     */
    private function checkImpossibleTravel($userId, $currentGeo) {
        $lastConnection = $this->db->select('connection_attempts', '*', ['user_id' => $userId], 'fetch', 'ORDER BY created_at DESC LIMIT 1 OFFSET 1');
        
        if (!$lastConnection) return ['impossible' => false];
        
        $lastGeo = json_decode($lastConnection['geo_info'], true);
        if (!$lastGeo || !isset($lastGeo['lat'])) return ['impossible' => false];

        // Calcular distância entre pontos
        $distance = $this->calculateDistance(
            $lastGeo['lat'], $lastGeo['lon'],
            $currentGeo['lat'] ?? 0, $currentGeo['lon'] ?? 0
        );

        // Tempo entre conexões
        $timeDiff = (time() - strtotime($lastConnection['created_at'])) / 3600; // horas
        
        // Velocidade necessária (km/h)
        $speed = $timeDiff > 0 ? $distance / $timeDiff : 9999;

        // Se velocidade > 800 km/h (avião), considerar suspeito
        if ($speed > 800 && $distance > 500) {
            return [
                'impossible' => true,
                'distance_km' => round($distance),
                'speed_kmh' => round($speed),
                'time_hours' => round($timeDiff, 2)
            ];
        }

        return ['impossible' => false];
    }

    /**
     * Calcula distância entre duas coordenadas (Fórmula de Haversine)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    /**
     * Registra tentativa de conexão para análise futura
     */
    private function recordConnectionAttempt($userId, $ip, $fingerprint, $geoInfo, $riskScore, $allowed) {
        $this->db->insert('connection_attempts', [
            'user_id' => $userId,
            'ip' => $ip,
            'fingerprint' => $fingerprint,
            'geo_info' => json_encode($geoInfo),
            'risk_score' => $riskScore,
            'allowed' => $allowed ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Log de violações detectadas
     */
    private function logViolation($userId, $ip, $type, $alerts) {
        $this->db->insert('security_violations', [
            'user_id' => $userId,
            'ip' => $ip,
            'violation_type' => $type,
            'details' => json_encode($alerts),
            'action_taken' => 'blocked',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->logger->warning("Violação de segurança detectada", [
            'user_id' => $userId,
            'type' => $type,
            'ip' => $ip
        ]);
    }

    /**
     * Parse de User-Agent
     */
    private function parseUserAgent($ua) {
        $browser = 'Unknown';
        $os = 'Unknown';
        $version = '';
        $platform = 'Unknown';

        // Detectar navegador
        if (preg_match('/Chrome\/([0-9.]+)/', $ua, $m)) {
            $browser = 'Chrome'; $version = $m[1];
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $ua, $m)) {
            $browser = 'Firefox'; $version = $m[1];
        } elseif (preg_match('/Safari\/([0-9.]+)/', $ua, $m)) {
            $browser = 'Safari'; $version = $m[1];
        }

        // Detectar OS
        if (preg_match('/Windows NT ([0-9.]+)/', $ua, $m)) {
            $os = 'Windows'; $platform = "NT {$m[1]}";
        } elseif (preg_match('/Mac OS X ([0-9_]+)/', $ua, $m)) {
            $os = 'macOS'; $platform = str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Android ([0-9.]+)/', $ua, $m)) {
            $os = 'Android'; $platform = $m[1];
        } elseif (preg_match('/Linux/', $ua)) {
            $os = 'Linux';
        }

        return compact('browser', 'version', 'os', 'platform');
    }

    /**
     * Dashboard de violações
     */
    public function getViolationsReport($limit = 50) {
        return $this->db->query("
            SELECT v.*, u.username, u.email
            FROM security_violations v
            JOIN users u ON v.user_id = u.id
            ORDER BY v.created_at DESC
            LIMIT $limit
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Estatísticas de segurança
     */
    public function getSecurityStats() {
        return [
            'total_violations_today' => $this->db->count('security_violations', ['created_at[>]' => date('Y-m-d 00:00:00')]),
            'blocked_connections' => $this->db->count('connection_attempts', ['allowed' => 0, 'created_at[>]' => date('Y-m-d 00:00:00')]),
            'vpn_detected' => $this->db->count('security_violations', ['violation_type' => 'VPN_DETECTED', 'created_at[>]' => date('Y-m-d 00:00:00')]),
            'high_risk_users' => $this->db->query("
                SELECT COUNT(DISTINCT user_id) FROM connection_attempts 
                WHERE risk_score > 70 AND created_at > ?
            ", [date('Y-m-d H:i:s', strtotime('-24 hours'))])->fetchColumn()
        ];
    }
}
