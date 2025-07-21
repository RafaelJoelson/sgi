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

    // --- FUNÇÕES DE CARREGAMENTO E FORMULÁRIO (RESTAURADAS) ---
    function carregarCota() {
        fetch('cota_servidor.php')
            .then(r => r.json())
            .then(data => {
                if(data.sucesso) {
                    document.getElementById('cota-info').innerText = `Cota PB: ${data.cota_pb_disponivel} páginas | Cota Colorida: ${data.cota_color_disponivel} páginas`;
                } else {
                    document.getElementById('cota-info').innerText = 'Não foi possível obter as cotas.';
                }
            });
    }

    function carregarSolicitacoes() {
        fetch('listar_solicitacoes.php')
            .then(r => r.json())
            .then(data => {
                let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>Arquivo / Tipo</th><th>Cópias</th><th>Páginas</th><th>Tipo</th><th>Status</th><th>Data</th></tr></thead><tbody>';
                if(data.length === 0) {
                    html += '<tr><td colspan="6">Nenhuma solicitação recente.</td></tr>';
                } else {
                    data.forEach(s => {
                        let nomeArquivoExibido = !s.arquivo ? '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>' : `<a href="download.php?id_solicitacao=${s.id}" target="_blank" title="Baixar ${s.arquivo}"><i class="fas fa-download"></i> ${s.arquivo}</a>`;
                        const dataExibicao = s.data_formatada || s.data;
                        html += `<tr>
                            <td>${nomeArquivoExibido}</td>
                            <td>${s.qtd_copias}</td>
                            <td>${s.qtd_paginas}</td>
                            <td>${s.colorida == 1 ? 'Colorida' : 'PB'}</td>
                            <td>${s.status}</td>
                            <td>${dataExibicao}</td>
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
        fetch('enviar_solicitacao.php', {
            method: 'POST',
            body: formData
        })
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
        const uploadInput = document.getElementById('arquivo');
        uploadInput.disabled = this.checked;
        this.checked ? uploadInput.removeAttribute('required') : uploadInput.setAttribute('required', 'required');
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