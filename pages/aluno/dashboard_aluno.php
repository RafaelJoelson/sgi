<?php
// Dashboard do Aluno
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'aluno') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_aluno.css">
<main class="container">
    <!-- NOVO: Container para as notificações "toast" -->
    <div id="toast-notification-container"></div>
        <button id="btn-ativar-notificacoes" class="btn-notificacao" title="Clique para permitir notificações no navegador">
            <i class="fas fa-bell"></i> Ativar Notificações
        </button>
    <h4>Aluno(a): <?= htmlspecialchars($_SESSION['usuario']['nome'] . (isset($_SESSION['usuario']['sobrenome']) ? ' ' . $_SESSION['usuario']['sobrenome'] : '')) ?></h4>
    <div id="cota-info" style="margin-bottom:1em;font-weight:bold;color:#1a4b2a;"></div>
    
    <form id="form-solicitacao" enctype="multipart/form-data">
        <label>Arquivo para impressão
            <input type="file" name="arquivo" id="arquivo" required accept=".pdf,.doc,.docx,.jpg,.png">
        </label>
        <label class="toggle-switch-label">
            <span>Solicitar cópia no balcão</span>
            <div class="toggle-switch">
                <input type="checkbox" id="solicitar_balcao" name="solicitar_balcao">
                <span class="slider"></span>
            </div>
        </label>
        <label>Quantidade de cópias
            <input type="number" name="qtd_copias" min="1" max="100" required>
        </label>
        <label>Número de páginas
            <input type="number" name="qtd_paginas" id="qtd_paginas" min="1" max="500" required placeholder="Informe o nº de páginas">
        </label>
        <button type="submit">Enviar Solicitação</button>
    </form>

    <section id="status-solicitacoes">
        <div class="section-header">
            <h2>Minhas Solicitações Recentes</h2>
        </div>
        <div id="tabela-solicitacoes"></div>
    </section>

    <button onclick="window.location.href='historico_solicitacoes.php'">Ver Histórico Completo</button>
</main>
<style>
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .btn-notificacao { background-color: #6c757d; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer; font-size: 0.9rem; }
    .btn-notificacao:hover { background-color: #5a6268; }
    .btn-notificacao i { margin-right: 5px; }

    /* NOVO: Estilos para o container de toasts */
    #toast-notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .toast-notification {
        background-color: #28a745;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .toast-notification.show {
        opacity: 1;
        transform: translateX(0);
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {

    // --- LÓGICA DE NOTIFICAÇÃO COMPLETA (TÍTULO, SOM E TOAST) ---
    const originalTitle = document.title;
    let notificationInterval = null;
    let audioContext;

    function playNotificationSound() {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.00001, audioContext.currentTime + 1);
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    }

    function startTitleFlash(message) {
        if (notificationInterval) return;
        let isToggled = false;
        notificationInterval = setInterval(() => {
            document.title = isToggled ? originalTitle : message;
            isToggled = !isToggled;
        }, 1500);
    }

    function stopTitleFlash() {
        clearInterval(notificationInterval);
        notificationInterval = null;
        document.title = originalTitle;
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            stopTitleFlash();
        }
    });

    function showOnPageToast(message) {
        const container = document.getElementById('toast-notification-container');
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (container.contains(toast)) {
                    container.removeChild(toast);
                }
            }, 500);
        }, 5000);
    }

    function handleNewNotification(message) {
        showOnPageToast(message);
        if (document.hidden) {
            playNotificationSound();
            startTitleFlash("Nova Notificação!");
        }
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification("Atualização de Solicitação", { body: message, icon: '../../favicon.ico' });
        }
    }

    function verificarNotificacoes() {
        fetch('verificar_notificacoes.php')
            .then(response => response.ok ? response.json() : Promise.reject(response))
            .then(data => {
                if (data.sucesso && data.notificacoes.length > 0) {
                    data.notificacoes.forEach(notificacao => {
                        handleNewNotification(notificacao.mensagem);
                    });
                    carregarSolicitacoes(); 
                }
            })
            .catch(error => console.error('Erro ao buscar notificações:', error));
    }

    // --- FUNÇÕES DE CARREGAMENTO E FORMULÁRIO ---
    function carregarCota() {
        fetch('cota_aluno.php').then(r => r.json()).then(data => {
            document.getElementById('cota-info').innerText = data.sucesso ? `Cota disponível: ${data.cota_disponivel} páginas` : 'Não foi possível obter a cota.';
        });
    }

    function carregarSolicitacoes() {
        fetch('listar_solicitacoes.php')
            .then(r => r.json())
            .then(data => {
                let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>Arquivo / Tipo</th><th>Cópias</th><th>Status</th><th>Data</th></tr></thead><tbody>';
                if (data.length === 0) {
                    html += '<tr><td colspan="4">Nenhuma solicitação recente.</td></tr>';
                } else {
                    data.forEach(s => {
                        let nomeArquivoExibido = !s.arquivo ? '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>' : `<a href="download.php?id_solicitacao=${s.id}" target="_blank" title="Baixar ${s.arquivo}"><i class="fas fa-download"></i> ${s.arquivo}</a>`;
                        html += `<tr>
                            <td>${nomeArquivoExibido}</td>
                            <td>${s.qtd_copias}</td>
                            <td>${s.status}</td>
                            <td>${s.data_formatada}</td>
                        </tr>`;
                    });
                }
                html += '</tbody></table>';
                document.getElementById('tabela-solicitacoes').innerHTML = html;
            });
    }

    const form = document.getElementById('form-solicitacao');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('enviar_solicitacao.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            alert(data.mensagem);
            if (data.sucesso) {
                form.reset();
                document.getElementById('arquivo').disabled = false;
                document.getElementById('arquivo').setAttribute('required', 'required');
                carregarSolicitacoes();
                carregarCota();
            }
        });
    });

    document.getElementById('solicitar_balcao').addEventListener('change', function () {
        const upload = document.getElementById('arquivo');
        upload.disabled = this.checked;
        this.checked ? upload.removeAttribute('required') : upload.setAttribute('required', 'required');
    });

    // --- LÓGICA DE ATIVAÇÃO DAS NOTIFICAÇÕES ---
    const btnAtivarNotificacoes = document.getElementById('btn-ativar-notificacoes');
    if (!("Notification" in window) || Notification.permission !== 'default') {
        btnAtivarNotificacoes.style.display = 'none';
    }
    btnAtivarNotificacoes.addEventListener('click', () => {
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                alert('Notificações ativadas com sucesso!');
                btnAtivarNotificacoes.style.display = 'none';
            } else {
                alert('Você bloqueou as notificações. Para ativá-las, altere as configurações do seu navegador.');
            }
        });
    });
    
    // Cargas iniciais e verificação periódica
    carregarCota();
    carregarSolicitacoes();
    setInterval(verificarNotificacoes, 15000);
});
</script>
<?php require_once '../../includes/footer.php'; ?>
