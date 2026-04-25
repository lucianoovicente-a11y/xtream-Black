<?php
/**
 * PaymentGateway.class.php
 * 
 * Módulo Financeiro com Integração de Gateways de Pagamento
 * Suporta: Mercado Pago, Stripe, PayPal e PIX
 * Automatiza ativação/desativação de contas baseada em pagamentos
 */

class PaymentGateway {
    private $db;
    private $logger;
    private $config;

    // Configurações dos gateways (idealmente viriam do .env)
    private $gateways = [
        'mercadopago' => [
            'access_token' => 'YOUR_ACCESS_TOKEN',
            'public_key' => 'YOUR_PUBLIC_KEY',
            'enabled' => true
        ],
        'stripe' => [
            'secret_key' => 'sk_test_YOUR_KEY',
            'publishable_key' => 'pk_test_YOUR_KEY',
            'webhook_secret' => 'whsec_YOUR_SECRET',
            'enabled' => true
        ],
        'paypal' => [
            'client_id' => 'YOUR_CLIENT_ID',
            'client_secret' => 'YOUR_CLIENT_SECRET',
            'mode' => 'sandbox', // sandbox ou live
            'enabled' => true
        ],
        'pix' => [
            'bank_code' => '001',
            'merchant_id' => 'YOUR_MERCHANT_ID',
            'certificate' => '/path/to/cert.pem',
            'enabled' => true
        ]
    ];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger('payments');
        $this->config = $this->loadConfig();
    }

    /**
     * Cria uma nova cobrança/pagamento
     */
    public function createPayment($userId, $amount, $method, $description = 'Assinatura XTream') {
        $user = $this->db->select('users', '*', ['id' => $userId], 'fetch');
        if (!$user) return ['success' => false, 'message' => 'Usuário não encontrado'];

        $paymentData = [
            'user_id' => $userId,
            'amount' => $amount,
            'method' => $method,
            'description' => $description,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+3 days'))
        ];

        try {
            switch ($method) {
                case 'mercadopago':
                    $result = $this->createMercadoPagoPayment($paymentData, $user);
                    break;
                case 'stripe':
                    $result = $this->createStripePayment($paymentData, $user);
                    break;
                case 'paypal':
                    $result = $this->createPayPalPayment($paymentData, $user);
                    break;
                case 'pix':
                    $result = $this->createPixPayment($paymentData, $user);
                    break;
                default:
                    return ['success' => false, 'message' => 'Método de pagamento inválido'];
            }

            if ($result['success']) {
                $paymentData['gateway_id'] = $result['gateway_id'];
                $paymentData['gateway_data'] = json_encode($result['data']);
                $paymentId = $this->db->insert('payments', $paymentData);
                
                $this->logger->info("Pagamento criado", ['payment_id' => $paymentId, 'user' => $user['username']]);
                
                return [
                    'success' => true,
                    'payment_id' => $paymentId,
                    'checkout_url' => $result['checkout_url'] ?? null,
                    'pix_code' => $result['pix_code'] ?? null,
                    'qr_code' => $result['qr_code'] ?? null
                ];
            }

            return $result;

        } catch (Exception $e) {
            $this->logger->error("Erro ao criar pagamento: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Processa webhook dos gateways (callback automático)
     */
    public function processWebhook($gateway, $payload) {
        $this->logger->info("Webhook recebido", ['gateway' => $gateway]);

        try {
            switch ($gateway) {
                case 'mercadopago':
                    return $this->handleMercadoPagoWebhook($payload);
                case 'stripe':
                    return $this->handleStripeWebhook($payload);
                case 'paypal':
                    return $this->handlePayPalWebhook($payload);
                case 'pix':
                    return $this->handlePixWebhook($payload);
                default:
                    return ['success' => false, 'message' => 'Gateway desconhecido'];
            }
        } catch (Exception $e) {
            $this->logger->error("Erro no webhook: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verifica status de pagamentos pendentes e atualiza contas
     */
    public function checkPendingPayments() {
        $pending = $this->db->select('payments', '*', ['status' => 'pending'], 'all');
        
        foreach ($pending as $payment) {
            if (strtotime($payment['expires_at']) < time()) {
                // Pagamento expirado
                $this->db->update('payments', ['status' => 'expired'], ['id' => $payment['id']]);
                continue;
            }

            // Consulta gateway para verificar status atual
            $status = $this->checkPaymentStatus($payment['method'], $payment['gateway_id']);
            
            if ($status === 'approved') {
                $this->activateSubscription($payment['user_id'], $payment['id']);
            } elseif ($status === 'rejected') {
                $this->db->update('payments', ['status' => 'rejected'], ['id' => $payment['id']]);
            }
        }
    }

    /**
     * Ativa assinatura do usuário após pagamento aprovado
     */
    private function activateSubscription($userId, $paymentId) {
        $this->db->beginTransaction();
        
        try {
            // Atualiza pagamento
            $this->db->update('payments', ['status' => 'approved', 'approved_at' => date('Y-m-d H:i:s')], ['id' => $paymentId]);
            
            // Busca dados do pagamento
            $payment = $this->db->select('payments', '*', ['id' => $paymentId], 'fetch');
            
            // Estende validade da conta (ex: +30 dias)
            $user = $this->db->select('users', '*', ['id' => $userId], 'fetch');
            $currentExpiry = strtotime($user['expiration_date'] ?? 'now');
            $newExpiry = date('Y-m-d H:i:s', max($currentExpiry, time()) + (30 * 24 * 60 * 60));
            
            $this->db->update('users', [
                'expiration_date' => $newExpiry,
                'status' => 'active'
            ], ['id' => $userId]);

            // Registra log
            $this->db->insert('subscription_logs', [
                'user_id' => $userId,
                'payment_id' => $paymentId,
                'action' => 'activation',
                'previous_expiry' => $user['expiration_date'],
                'new_expiry' => $newExpiry,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();
            $this->logger->info("Assinatura ativada", ['user_id' => $userId, 'payment_id' => $paymentId]);
            
            // Envia email de confirmação (implementar Mailer)
            // $this->sendActivationEmail($user);
            
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logger->error("Erro ao ativar assinatura: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desativa conta por inadimplência
     */
    public function suspendSubscription($userId, $reason = 'Non-payment') {
        $this->db->update('users', ['status' => 'suspended'], ['id' => $userId]);
        
        // Derruba conexões ativas
        $cm = new ConnectionManager();
        $cm->forceLogoutAll($userId);
        
        $this->logger->warning("Conta suspensa", ['user_id' => $userId, 'reason' => $reason]);
        
        // Registra suspensão
        $this->db->insert('subscription_logs', [
            'user_id' => $userId,
            'action' => 'suspension',
            'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    // --- Implementações Específicas dos Gateways ---

    private function createMercadoPagoPayment($data, $user) {
        // Simulação - Em produção usar SDK oficial do Mercado Pago
        $preference = [
            'items' => [[
                'title' => $data['description'],
                'quantity' => 1,
                'unit_price' => $data['amount']
            ]],
            'payer' => [
                'email' => $user['email']
            ],
            'back_urls' => [
                'success' => SITE_URL . '/payment/success',
                'failure' => SITE_URL . '/payment/failure',
                'pending' => SITE_URL . '/payment/pending'
            ],
            'external_reference' => $data['user_id']
        ];

        // API call simulada
        $gatewayId = 'MP-' . uniqid();
        $checkoutUrl = "https://www.mercadopago.com/checkout?pref_id={$gatewayId}";

        return [
            'success' => true,
            'gateway_id' => $gatewayId,
            'checkout_url' => $checkoutUrl,
            'data' => $preference
        ];
    }

    private function createStripePayment($data, $user) {
        // Simulação - Em produção usar SDK oficial do Stripe
        $gatewayId = 'stripe_' . uniqid();
        
        return [
            'success' => true,
            'gateway_id' => $gatewayId,
            'checkout_url' => "https://checkout.stripe.com/pay/{$gatewayId}",
            'data' => ['amount' => $data['amount'] * 100, 'currency' => 'BRL']
        ];
    }

    private function createPayPalPayment($data, $user) {
        // Simulação
        $gatewayId = 'PAYID-' . uniqid();
        
        return [
            'success' => true,
            'gateway_id' => $gatewayId,
            'checkout_url' => "https://www.paypal.com/checkoutnow?token={$gatewayId}",
            'data' => []
        ];
    }

    private function createPixPayment($data, $user) {
        // Simulação - Em produção integrar com API do banco
        $pixCode = '00020126580014BR.GOV.BCB.PIX...' . uniqid();
        $qrCode = 'data:image/png;base64,' . base64_encode('QR_CODE_SIMULADO');
        
        return [
            'success' => true,
            'gateway_id' => 'PIX-' . uniqid(),
            'pix_code' => $pixCode,
            'qr_code' => $qrCode,
            'data' => ['amount' => $data['amount']]
        ];
    }

    private function handleMercadoPagoWebhook($payload) {
        // Validar signature e processar
        if ($payload['type'] === 'payment' && $payload['action'] === 'approved') {
            $externalRef = $payload['data']['external_reference'];
            $payment = $this->db->select('payments', '*', ['gateway_id' => $payload['data']['id']], 'fetch');
            if ($payment) {
                $this->activateSubscription($payment['user_id'], $payment['id']);
            }
        }
        return ['success' => true];
    }

    private function handleStripeWebhook($payload) {
        if ($payload['type'] === 'checkout.session.completed') {
            // Processar pagamento aprovado
        }
        return ['success' => true];
    }

    private function handlePayPalWebhook($payload) {
        if ($payload['event_type'] === 'PAYMENT.CAPTURE.COMPLETED') {
            // Processar pagamento aprovado
        }
        return ['success' => true];
    }

    private function handlePixWebhook($payload) {
        if ($payload['status'] === 'COMPLETED') {
            // Processar PIX pago
        }
        return ['success' => true];
    }

    private function checkPaymentStatus($method, $gatewayId) {
        // Consultar API do gateway para status atual
        // Retorna: 'pending', 'approved', 'rejected', 'refunded'
        return 'approved'; // Simulação
    }

    private function loadConfig() {
        // Carregar configurações do .env ou banco
        return [];
    }

    /**
     * Gera relatório financeiro
     */
    public function getFinancialReport($startDate, $endDate) {
        return $this->db->query("
            SELECT 
                method,
                COUNT(*) as total_payments,
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                AVG(amount) as avg_ticket
            FROM payments
            WHERE created_at BETWEEN ? AND ?
            GROUP BY method
        ", [$startDate, $endDate])->fetchAll(PDO::FETCH_ASSOC);
    }
}
