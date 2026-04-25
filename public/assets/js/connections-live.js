/**
 * XTREAM SERVER - ATUALIZAÇÃO EM TEMPO REAL DE CONEXÕES
 * Força atualização automática dos dados de clientes online
 */

(function() {
    'use strict';

    // Configurações
    const UPDATE_INTERVAL = 3000; // 3 segundos
    const API_ENDPOINT = '/api/connection/stats';
    
    // Função para buscar dados atualizados
    async function fetchConnections() {
        try {
            const response = await fetch(API_ENDPOINT, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) throw new Error('Falha na requisição');
            
            const data = await response.json();
            updateTable(data);
        } catch (error) {
            console.error('Erro ao atualizar conexões:', error);
        }
    }
    
    // Função para atualizar a tabela
    function updateTable(data) {
        const tableBody = document.querySelector('.table-online tbody');
        if (!tableBody) return;
        
        tableBody.innerHTML = '';
        
        if (!data.connections || data.connections.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhuma conexão ativa</td></tr>';
            return;
        }
        
        data.connections.forEach(conn => {
            const row = document.createElement('tr');
            
            // Formatar tempo online
            const onlineTime = formatOnlineTime(conn.last_activity);
            
            // Determinar tipo de conteúdo
            const contentType = conn.stream_type || 'live';
            const contentName = conn.channel_name || conn.movie_name || conn.series_name || 'Desconhecido';
            
            row.innerHTML = `
                <td>${conn.username || '-'}</td>
                <td>${conn.ip_address || '-'}</td>
                <td>${conn.user_agent ? conn.user_agent.substring(0, 30) + '...' : '-'}</td>
                <td><span class="badge badge-${contentType === 'live' ? 'success' : 'info'}">${contentType.toUpperCase()}</span></td>
                <td>${contentName}</td>
                <td><strong>${onlineTime}</strong></td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="disconnectUser(${conn.id})">
                        <i class="fa fa-times"></i> Desconectar
                    </button>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
        
        // Atualizar contador
        const counter = document.querySelector('#online-count');
        if (counter) {
            counter.textContent = data.total_connections || 0;
        }
    }
    
    // Formatar tempo online
    function formatOnlineTime(lastActivity) {
        const now = new Date();
        const last = new Date(lastActivity);
        const diffMs = now - last;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        
        if (diffMins < 1) return 'Agora';
        if (diffMins < 60) return `${diffMins} min`;
        if (diffHours < 24) return `${diffHours}h ${diffMins % 60}min`;
        return `${diffHours}h`;
    }
    
    // Função global para desconectar usuário
    window.disconnectUser = function(userId) {
        if (!confirm('Tem certeza que deseja desconectar este usuário?')) return;
        
        fetch('/api/connection/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Usuário desconectado com sucesso!');
                fetchConnections(); // Atualiza imediatamente
            } else {
                alert('Erro ao desconectar: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro na requisição: ' + error);
        });
    };
    
    // Iniciar atualização automática
    function startAutoUpdate() {
        fetchConnections(); // Primeira carga imediata
        setInterval(fetchConnections, UPDATE_INTERVAL); // Atualizações periódicas
    }
    
    // Iniciar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startAutoUpdate);
    } else {
        startAutoUpdate();
    }
    
    console.log('✅ Sistema de atualização de conexões iniciado!');
})();
