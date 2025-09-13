document.addEventListener('DOMContentLoaded', () => {
    // --- ELEMENTOS DO DOM ---
    const cotaInfoEl = document.getElementById('cota-info');
    const tabelaCorpo = document.querySelector('#tabela-solicitacoes tbody');
    const formSolicitacao = document.getElementById('form-solicitacao');
    const toastContainer = document.getElementById('toast-notification-container');
    const audioNotificacao = new Audio('../../sounds/notification.mp3');
    const btnAtivarNotificacoes = document.getElementById('btn-ativar-notificacoes');

    // --- HISTÓRICO COMPLETO ---
    async function carregarHistoricoSolicitacoes() {
        try {
            const response = await fetch('./functions/get_historico_solicitacoes_aluno.php');
            const data = await response.json();
            const tabelaHistorico = document.querySelector('#tabela-historico tbody');
            if (data.sucesso && tabelaHistorico) {
                tabelaHistorico.innerHTML = '';
                if (data.solicitacoes.length === 0) {
                    tabelaHistorico.innerHTML = '<tr><td colspan="5" style="text-align:center;">Nenhum registro encontrado.</td></tr>';
                } else {
                    data.solicitacoes.forEach(s => {
                        const dataFormatada = new Date(s.data_criacao).toLocaleString('pt-BR');
                        const arquivoHtml = s.arquivo_path
                            ? `<a href="./functions/download.php?id_solicitacao=${s.id}" title="Baixar ${s.arquivo_path}"><i class="fas fa-download"></i> ${s.arquivo_path}</a>`
                            : '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>';
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td data-label="Arquivo">${arquivoHtml}</td>
                            <td data-label="Cópias">${s.qtd_copias}</td>
                            <td data-label="Páginas">${s.qtd_paginas}</td>
                            <td data-label="Status">${s.status}</td>
                            <td data-label="Data">${dataFormatada}</td>
                        `;
                        tabelaHistorico.appendChild(row);
                    });
                }
            }
        } catch (error) {
            console.error('Erro ao carregar histórico:', error);
        }
    }

    // --- FUNÇÕES DE UI E NOTIFICAÇÃO ---
    function showToast(message, type = 'sucesso') {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        }, 100);
    }

    if (btnAtivarNotificacoes) {
        btnAtivarNotificacoes.addEventListener('click', () => {
            if (Notification.permission !== 'granted') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        showToast('Notificações ativadas!', 'sucesso');
                        btnAtivarNotificacoes.style.display = 'none';
                    }
                });
            }
        });
        if (Notification.permission === 'granted') {
            btnAtivarNotificacoes.style.display = 'none';
        }
    }

    // --- LÓGICA DE DADOS ---
    async function carregarCotas() {
        try {
            const response = await fetch('./functions/get_cota_aluno.php');
            const data = await response.json();
            if (data.sucesso && data.cota) {
                const usada = parseInt(data.cota.cota_usada, 10);
                const total = parseInt(data.cota.cota_total, 10);
                const disponivel = total - usada;
                if (cotaInfoEl) {
                    cotaInfoEl.innerHTML = `Cota da Turma: <strong>${disponivel} / ${total}</strong> páginas disponíveis.`;
                }
            } else {
                if (cotaInfoEl) cotaInfoEl.textContent = data.mensagem || 'Não foi possível carregar as cotas.';
            }
        } catch (error) {
            console.error('Erro ao carregar cotas:', error);
            if (cotaInfoEl) cotaInfoEl.textContent = 'Erro de conexão ao buscar cotas.';
        }
    }

    async function carregarSolicitacoes() {
        try {
            const response = await fetch('./functions/get_solicitacoes_aluno.php');
            const data = await response.json();
            const tabelaSolicitacoes = document.getElementById('tabela-solicitacoes');
            if (data.sucesso && tabelaSolicitacoes) {
                let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>Arquivo / Tipo</th><th>Cópias</th><th>Páginas</th><th>Status</th><th>Data</th></tr></thead><tbody>';
                if (data.solicitacoes.length === 0) {
                    html += '<tr><td colspan="5" style="text-align:center;">Nenhuma solicitação recente.</td></tr>';
                } else {
                    data.solicitacoes.forEach(s => {
                        const dataFormatada = new Date(s.data_criacao).toLocaleString('pt-BR');
                        const arquivoHtml = s.arquivo_path
                            ? `<a href="./functions/download.php?id_solicitacao=${s.id}" title="Baixar ${s.arquivo_path}"><i class="fas fa-download"></i> ${s.arquivo_path}</a>`
                            : '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>';
                        html += `
                            <tr>
                                <td data-label="Arquivo">${arquivoHtml}</td>
                                <td data-label="Cópias">${s.qtd_copias}</td>
                                <td data-label="Páginas">${s.qtd_paginas}</td>
                                <td data-label="Status">${s.status}</td>
                                <td data-label="Data">${dataFormatada}</td>
                            </tr>
                        `;
                    });
                }
                html += '</tbody></table>';
                tabelaSolicitacoes.innerHTML = html;
            }
        } catch (error) {
            console.error('Erro ao carregar solicitações:', error);
        }
    }

    async function verificarNotificacoes() {
        try {
            const response = await fetch('./functions/verificar_notificacoes.php');
            const data = await response.json();
            if (data.sucesso && data.notificacoes.length > 0) {
                audioNotificacao.play();
                data.notificacoes.forEach(notif => {
                    showToast(notif.mensagem, 'info');
                });
                carregarSolicitacoes();
                carregarCotas();
            }
        } catch (error) {
            console.error('Erro ao verificar notificações:', error);
        }
    }

    // --- EVENTOS DO FORMULÁRIO ---
    if (formSolicitacao) {
        const inputArquivo = document.getElementById('arquivo');
        const inputPaginas = document.getElementById('qtd_paginas');
        const chkBalcao = document.getElementById('solicitar_balcao');

        // Lógica para auto-preencher páginas do PDF
        inputArquivo.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (file.type === 'application/pdf') {
                try {
                    const arrayBuffer = await file.arrayBuffer();
                    const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    inputPaginas.value = pdf.numPages;
                    inputPaginas.readOnly = true;
                    showToast(`PDF com ${pdf.numPages} página(s) detectado.`, 'info');
                } catch (error) {
                    showToast('Erro ao ler PDF. Insira as páginas manualmente.', 'erro');
                    inputPaginas.readOnly = false;
                }
            } else {
                inputPaginas.readOnly = false;
            }
        });

        // Lógica para solicitação no balcão
        chkBalcao.addEventListener('change', function() {
            const isChecked = this.checked;
            inputArquivo.disabled = isChecked;
            inputArquivo.required = !isChecked;
            inputPaginas.readOnly = isChecked;
            if (isChecked) {
                inputArquivo.value = ''; // Limpa qualquer arquivo selecionado
                inputPaginas.value = 1;
                showToast('Solicitação no balcão: número de páginas fixado em 1.', 'info');
            } else {
                inputPaginas.value = '';
            }
        });

        // Processamento do envio
        formSolicitacao.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btnSubmit = formSolicitacao.querySelector('button[type="submit"]');
            const formData = new FormData(formSolicitacao);
            
            // Garante que o valor de um campo readonly seja enviado
            if (inputPaginas.readOnly) {
                formData.set('qtd_paginas', inputPaginas.value);
            }

            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Enviando...';

            try {
                const response = await fetch('./functions/processar_solicitacao_aluno.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                showToast(data.mensagem, data.sucesso ? 'sucesso' : 'erro');
                if (data.sucesso) {
                    formSolicitacao.reset();
                    inputPaginas.readOnly = false;
                    inputArquivo.disabled = false;
                    chkBalcao.checked = false;
                    carregarCotas();
                    carregarSolicitacoes();
                }
            } catch (error) {
                showToast('Erro de conexão ao enviar solicitação.', 'erro');
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Enviar Solicitação';
            }
        });
    }

    // --- INICIALIZAÇÃO ---
    carregarCotas();
    carregarSolicitacoes();
    setInterval(verificarNotificacoes, 15000); // Verifica a cada 15 segundos
});