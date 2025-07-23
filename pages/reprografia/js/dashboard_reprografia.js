document.addEventListener('DOMContentLoaded', () => {
    let ultimosIds = [];
    const originalTitle = document.title;
    let notificationInterval = null;
    let audioContext;

    // --- LÓGICA DE NOTIFICAÇÃO COMPLETA ---
    function playNotificationSound() {
        if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(900, audioContext.currentTime);
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

    document.addEventListener('visibilitychange', () => { if (!document.hidden) stopTitleFlash(); });

    function showOnPageToast(message) {
        const container = document.getElementById('toast-notification-container');
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.innerHTML = `<i class="fas fa-bell"></i> ${message}`;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => { if (container.contains(toast)) container.removeChild(toast); }, 500);
        }, 5000);
    }

    function handleNewNotification(message) {
        showOnPageToast(message);
        if (document.hidden) {
            playNotificationSound();
            startTitleFlash("Nova Solicitação!");
        }
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification("SGI - Reprografia", { body: message, icon: '../../favicon.ico' });
        }
    }

    // --- FUNÇÕES PRINCIPAIS DA PÁGINA ---
    function carregarSolicitacoes(notify = false) {
        fetch('./functions/listar_solicitacoes_pendentes.php')
            .then(r => r.json())
            .then(data => {
                let html = '<table class="table table-striped table-hover"><thead><tr><th>Arquivo / Tipo</th><th>Solicitante</th><th>Cópias</th><th>Páginas</th><th>Colorida</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead><tbody>';
                if (data.length === 0) {
                    html += '<tr><td colspan="8" class="text-center">Nenhuma solicitação pendente.</td></tr>';
                } else {
                    data.forEach(s => {
                        let linkArquivo = s.arquivo ? `<a href="./functions/download_arquivo.php?id_solicitacao=${s.id}" target="_blank" title="Baixar ${s.arquivo}"><i class="fas fa-download"></i> ${s.arquivo}</a>` : '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>';
                        html += `<tr>
                            <td>${linkArquivo}</td><td>${s.nome_solicitante}</td><td>${s.qtd_copias}</td>
                            <td><input type="number" class="form-control form-control-sm" style="width: 70px;" value="${s.qtd_paginas}" onchange="editarPaginas(${s.id}, this.value)"></td>
                            <td><span class="badge ${s.colorida == 1 ? 'badge-info' : 'badge-secondary'}">${s.colorida == 1 ? 'Sim' : 'Não'}</span></td>
                            <td>${s.status}</td><td>${s.data}</td>
                            <td class="actions">
                                <button title="Aceitar" class="btn-accept" onclick="atualizarStatus(${s.id},'Aceita')"><i class="fas fa-check"></i></button>
                                <button title="Rejeitar" class="btn-reject" onclick="atualizarStatus(${s.id},'Rejeitada')"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>`;
                    });
                }
                html += '</tbody></table>';
                document.getElementById('tabela-solicitacoes').innerHTML = html;

                const idsAtuais = data.map(s => s.id);
                if (notify && ultimosIds.length > 0) {
                    const novasSolicitacoes = idsAtuais.filter(id => !ultimosIds.includes(id));
                    if (novasSolicitacoes.length > 0) {
                        const mensagem = `Você recebeu ${novasSolicitacoes.length} nova(s) solicitação(ões)!`;
                        handleNewNotification(mensagem);
                    }
                }
                ultimosIds = idsAtuais;
            });
    }

    // CORREÇÃO: Funções restauradas
    window.atualizarStatus = function(id, status) {
        if (!confirm(`Tem certeza que deseja "${status}" esta solicitação?`)) return;
        fetch('./functions/atualizar_status_solicitacao.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&status=${status}`
        }).then(r => r.json()).then(data => {
            alert(data.mensagem);
            if (data.sucesso) carregarSolicitacoes();
        });
    }

    window.editarPaginas = function(id, valor) {
        fetch('./functions/editar_paginas.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&qtd_paginas=${valor}`
        }).then(r => r.json()).then(data => {
            if (!data.sucesso) alert(data.mensagem);
        });
    }

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

    carregarSolicitacoes();
    setInterval(() => carregarSolicitacoes(true), 15000);

    // --- LÓGICA DO MODAL DE EDIÇÃO ---
    const modalEditar = document.getElementById('modal-editar-dados');
    const formEditar = document.getElementById('form-editar-reprografia');

    document.getElementById('btn-alterar-dados').addEventListener('click', (e) => {
        e.preventDefault();
        fetch('./functions/obter_dados_reprografia.php')
            .then(response => response.json())
            .then(data => {
                if (data.sucesso && data.dados) {
                    document.getElementById('reprografia-id').value = data.dados.id;
                    document.getElementById('reprografia-login').value = data.dados.login;
                    document.getElementById('reprografia-nome').value = data.dados.nome;
                    document.getElementById('reprografia-sobrenome').value = data.dados.sobrenome;
                    document.getElementById('reprografia-email').value = data.dados.email;
                    modalEditar.style.display = 'block';
                } else {
                    alert('Falha ao obter dados: ' + data.mensagem);
                }
            });
    });

    document.getElementById('close-modal-editar').addEventListener('click', () => modalEditar.style.display = 'none');
    window.addEventListener('click', (e) => { if (e.target === modalEditar) modalEditar.style.display = 'none'; });

    formEditar.addEventListener('submit', function(e) {
        e.preventDefault();
        const novaSenha = document.getElementById('reprografia-nova-senha').value;
        const confirmaSenha = document.getElementById('reprografia-confirma-senha').value;
        const msgErro = document.getElementById('mensagem-modal-erro');

        if (novaSenha !== confirmaSenha) {
            msgErro.textContent = 'As senhas não coincidem.';
            msgErro.style.display = 'block';
            return;
        }
        msgErro.style.display = 'none';

        const formData = new FormData(formEditar);
        fetch('./functions/processar_edicao_reprografia.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                modalEditar.style.display = 'none';
                showOnPageToast(data.mensagem); // Usa o toast para sucesso
                if (data.novo_nome) {
                    const userInfo = document.querySelector('.user-info h3');
                    if (userInfo) userInfo.textContent = `Bem-vindo: ${data.novo_nome}`;
                }
            } else {
                msgErro.textContent = data.mensagem;
                msgErro.style.display = 'block';
            }
        });
    });
});