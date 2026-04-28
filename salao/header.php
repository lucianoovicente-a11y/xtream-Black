<?php $config = getConfig($conn); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#e91e63">
    <link rel="manifest" href="manifest.json">
    <title>Agenda Bia Manicure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #e91e63;
            --secondary: #fce4ec;
            --accent: #ad1457;
        }
        * { -webkit-tap-highlight-color: transparent; }
        body { 
            background: #fafafa; 
            font-size: 16px;
            overscroll-behavior-y: none;
        }
        
        .sidebar {
            min-height: 100vh;
            max-height: 100vh;
            background: linear-gradient(180deg, var(--primary), var(--accent));
            color: white;
            position: fixed;
            top: 0;
            left: -220px;
            width: 200px;
            z-index: 9999;
            transition: left 0.3s ease;
            overflow-y: auto;
            padding-bottom: 80px;
        }
        .sidebar.active { left: 0; }
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9998;
            display: none;
        }
        .sidebar-overlay.active { display: block; }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            border-radius: 8px;
            margin: 4px 12px;
            transition: background 0.2s;
            font-size: 15px;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.25);
        }
        
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            z-index: 9997;
            align-items: center;
            padding: 0 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            padding: 8px;
            cursor: pointer;
        }
        
        .mobile-logo {
            flex: 1;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-right: 44px;
        }
        
        .main-content {
            padding-top: 70px;
            min-height: 100vh;
        }
        
        .card { 
            border: none; 
            border-radius: 12px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); 
            margin-bottom: 16px;
        }
        .card-header { 
            background: white; 
            border-bottom: 2px solid var(--secondary); 
            font-weight: 600;
            padding: 14px 16px;
        }
        .card-body { padding: 16px; }
        
        .btn-primary { 
            background: var(--primary); 
            border: none; 
            padding: 10px 20px;
            font-size: 15px;
        }
        .btn-primary:hover { background: var(--accent); }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .stat-card h6 { font-size: 13px; opacity: 0.9; }
        .stat-card h3 { font-size: 22px; margin: 5px 0 0 0; }
        
        .stat-card.green { background: linear-gradient(135deg, #4caf50, #2e7d32); }
        .stat-card.orange { background: linear-gradient(135deg, #ff9800, #ef6c00); }
        
        .calendar-day {
            min-height: 80px;
            border: 1px solid #eee;
            padding: 4px;
            font-size: 11px;
        }
        .calendar-day.today { background: var(--secondary); }
        .calendar-event {
            background: var(--primary);
            color: white;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 10px;
            margin: 1px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .status-agendado { background: #2196f3; }
        .status-concluido { background: #4caf50; }
        .status-cancelado { background: #f44336; }
        
        table { font-size: 14px; }
        th { font-weight: 600; white-space: nowrap; }
        td, th { padding: 10px 8px !important; }
        
        .table-responsive { margin: -1px; }
        
        .modal-dialog { margin: 10px; }
        .modal-content { border-radius: 12px; }
        
        .form-label { font-weight: 500; font-size: 14px; }
        .form-control, .form-select {
            padding: 10px 12px;
            font-size: 15px;
            border-radius: 8px;
        }
        
        .badge { font-size: 11px; padding: 5px 8px; }
        
        .h2, h2 { font-size: 1.5rem; margin-bottom: 16px !important; }
        
        @media (max-width: 768px) {
            .sidebar { display: block; }
            .mobile-header { display: flex; }
            .main-content { 
                padding-top: 70px; 
                padding-left: 0;
                padding-right: 0;
                padding-bottom: 20px;
            }
            .col-md-10 { 
                width: 100%; 
                padding: 12px !important;
            }
            .col-md-2 { display: none; }
            
            .stat-card { padding: 14px; }
            .stat-card h3 { font-size: 20px; }
            
            .calendar-day { min-height: 60px; font-size: 10px; }
            .calendar-event { font-size: 9px; }
            
            .d-flex.justify-content-between { flex-direction: column; gap: 8px; }
            .d-flex.justify-content-between > div { width: 100%; }
            
            .modal-dialog { margin: 10px; max-width: calc(100% - 20px); }
            .modal-body { padding: 16px; }
            
            .btn-group { display: flex; flex-wrap: wrap; gap: 5px; }
            
            .page-content { padding: 0 8px; }
        }
        
        @media (min-width: 769px) {
            .sidebar { left: 0 !important; width: 200px; }
            .main-content { margin-left: 200px; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    
    <div class="mobile-header">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <span class="mobile-logo">Bia Manicure</span>
    </div>

    <div class="sidebar p-3">
        <h4 class="text-center mb-4" style="margin-top: 50px;"><i class="bi bi-scissors"></i> Bia Manicure</h4>
        <a href="?acao=dashboard" class="<?= $acao == 'dashboard' ? 'active' : '' ?>" onclick="closeSidebar()"><i class="bi bi-house me-2"></i> Dashboard</a>
        <a href="?acao=agendamentos" class="<?= $acao == 'agendamentos' ? 'active' : '' ?>" onclick="closeSidebar()"><i class="bi bi-calendar-check me-2"></i> Agendamentos</a>
        <a href="?acao=calendario" class="<?= $acao == 'calendario' ? 'active' : '' ?>" onclick="closeSidebar()"><i class="bi bi-calendar3 me-2"></i> Calendário</a>
        <a href="?acao=clientes" class="<?= $acao == 'clientes' ? 'active' : '' ?>" onclick="closeSidebar()"><i class="bi bi-people me-2"></i> Clientes</a>
        <a href="?acao=servicos" class="<?= $acao == 'servicos' ? 'active' : '' ?>" onclick="closeSidebar()"><i class="bi bi-scissors me-2"></i> Serviços</a>
        <a href="?acao=despesas" class="<?= $acao == 'despesas' ? 'active' : '' ?>" onclick="closeSidebar()"><i class="bi bi-cash me-2"></i> Despesas</a>
        <a href="?acao=cobrancas" class="<?= $acao == 'cobrancas' ? 'active' : '' ?>" onclick="closeSidebar()"><i class="bi bi-currency-dollar me-2"></i> Cobranças</a>
        <a href="?acao=financas" class="<?= $acao == 'financas' ? 'active' : '' ?>" onclick="closeSidebar()"><i class="bi bi-graph-up me-2"></i> Finanças</a>
        <a href="?acao=config" class="<?= $acao == 'config' ? 'active' : '' ?>" onclick="closeSidebar()"><i class="bi bi-gear me-2"></i> Configurações</a>
    </div>
    
    <div class="main-content">
        <div class="page-content">
            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 10px;">
                    <?= $mensagem ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
