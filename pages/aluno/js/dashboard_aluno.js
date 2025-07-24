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

    function showOnPageToast(message, isError = false) {
        const container = document.getElementById('toast-notification-container');
        if (!container) {
            console.error('Contêiner de toast não encontrado.');
            return;
        }
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.backgroundColor = isError ? '#f44336' : '#4CAF50';
        toast.innerHTML = `<i class="fas fa-${isError ? 'exclamation-circle' : 'check-circle'}"></i> ${message}`;
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

    function handleNewNotification(message, status) {
        showOnPageToast(message);
        if (document.hidden) {
            playNotificationSound();
            startTitleFlash("Nova Notificação!");
        }
        if ("Notification" in window && Notification.permission === "granted") {
            new Notification("Atualização de Solicitação", { body: message, icon: '../../favicon.ico' });
        }
        if (status === 'Aceita') {
            carregarCota();
        }
    }

    function verificarNotificacoes() {
        fetch('./functions/verificar_notificacoes.php')
            .then(response => response.ok ? response.json() : Promise.reject(response))
            .then(data => {
                if (data.sucesso && data.notificacoes.length > 0) {
                    data.notificacoes.forEach(notificacao => {
                        handleNewNotification(notificacao.mensagem, notificacao.status);
                    });
                    carregarSolicitacoes();
                }
            })
            .catch(error => console.error('Erro ao buscar notificações:', error));
    }

    // --- FUNÇÕES DE CARREGAMENTO E FORMULÁRIO ---
    function carregarCota() {
        fetch('./functions/cota_aluno.php')
            .then(r => r.json())
            .then(data => {
                document.getElementById('cota-info').innerText = data.sucesso ? `Cota disponível: ${data.cota_disponivel} páginas` : 'Não foi possível obter a cota.';
            });
    }

    function carregarSolicitacoes() {
        fetch('./functions/listar_solicitacoes.php')
            .then(r => r.json())
            .then(data => {
                let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>Arquivo / Tipo</th><th>Cópias</th><th>Status</th><th>Data</th></tr></thead><tbody>';
                if (data.length === 0) {
                    html += '<tr><td colspan="4">Nenhuma solicitação recente.</td></tr>';
                } else {
                    data.forEach(s => {
                        let nomeArquivoExibido = !s.arquivo ? '<strong><i class="fas fa-store-alt"></i> <em>Solicitação no Balcão</em></strong>' : `<a href="./functions/download.php?id_solicitacao=${s.id}" target="_blank" title="Baixar ${s.arquivo}"><i class="fas fa-download"></i> ${s.arquivo}</a>`;
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

    // --- VALIDAÇÃO DO ARQUIVO ---
    const inputArquivo = document.getElementById('arquivo');
    const inputPaginas = document.getElementById('qtd_paginas');
    if (inputArquivo) {
        inputArquivo.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) {
                inputPaginas.value = '';
                inputPaginas.disabled = false;
                atualizarPreview();
                return;
            }

            const fileExtension = file.name.split('.').pop().toLowerCase();
            const validExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'odt'];

            // Verificar extensão válida
            if (!validExtensions.includes(fileExtension)) {
                showOnPageToast('Extensão de arquivo inválida. Permitido: .pdf, .jpg, .jpeg, .png, .doc, .docx, .odt', true);
                inputArquivo.value = '';
                inputPaginas.value = '';
                inputPaginas.disabled = false;
                atualizarPreview();
                return;
            }

            // Lógica para imagens
            if (['jpg', 'jpeg', 'png'].includes(fileExtension)) {
                inputPaginas.value = 1;
                inputPaginas.disabled = true;
                showOnPageToast('Arquivo de imagem selecionado. Número de páginas fixado em 1.');
            }
            // Lógica para PDFs
            else if (fileExtension === 'pdf') {
                try {
                    const arrayBuffer = await file.arrayBuffer();
                    const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    const numPages = pdf.numPages;
                    inputPaginas.value = numPages;
                    inputPaginas.disabled = true;
                    showOnPageToast(`PDF com ${numPages} página(s) detectado.`);
                } catch (error) {
                    showOnPageToast('Erro ao carregar o PDF. Por favor, insira o número de páginas manualmente.', true);
                    inputPaginas.value = '';
                    inputPaginas.disabled = false;
                }
            }
            // Lógica para documentos do Office
            else if (['doc', 'docx', 'odt'].includes(fileExtension)) {
                inputPaginas.value = '';
                inputPaginas.disabled = false;
                showOnPageToast('Documento do Office selecionado. Insira o número de páginas manualmente.');
            }

            // Atualizar preview após validação do arquivo
            inputPaginas.dispatchEvent(new Event('input')); // Disparar evento input para atualizar o preview
            atualizarPreview();
        });
    }

    // --- LÓGICA DO FORMULÁRIO ---
    const form = document.getElementById('form-solicitacao');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const copiasInput = document.getElementById('qtd_copias');
        const paginasInput = document.getElementById('qtd_paginas');
        const copias = parseInt(copiasInput.value, 10) || 0;
        let paginas = parseInt(paginasInput.value, 10) || 0;
        const file = inputArquivo.files[0];
        const solicitarBalcao = document.getElementById('solicitar_balcao').checked;

        // Se qtd_paginas está desativado, usar o valor atual do campo
        if (paginasInput.disabled) {
            paginas = parseInt(paginasInput.value, 10) || 1; // Usa 1 como padrão se inválido
            paginasInput.value = paginas; // Garante que o valor esteja no campo
        }

        // Garantir que qtd_paginas seja 1 para solicitação no balcão
        if (solicitarBalcao) {
            paginas = 1;
            paginasInput.value = 1;
        }

        if (!solicitarBalcao && !file) {
            showOnPageToast('Por favor, selecione um arquivo.', true);
            return;
        }
        if (isNaN(copias) || copias <= 0 || copias > 100) {
            showOnPageToast('A quantidade de cópias deve estar entre 1 e 100.', true);
            return;
        }
        if (isNaN(paginas) || paginas <= 0 || paginas > 500) {
            showOnPageToast('O número de páginas deve estar entre 1 e 500.', true);
            return;
        }

        // Reativar temporariamente qtd_paginas para incluir no FormData
        const wasDisabled = paginasInput.disabled;
        if (wasDisabled) {
            paginasInput.disabled = false;
        }

        const formData = new FormData(form);
        fetch('./functions/enviar_solicitacao.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                showOnPageToast(data.mensagem, !data.sucesso);
                if (data.sucesso) {
                    form.reset();
                    inputArquivo.disabled = false;
                    inputArquivo.setAttribute('required', 'required');
                    inputPaginas.disabled = false;
                    document.getElementById('preview-copias-paginas').innerText = '';
                    carregarSolicitacoes();
                    carregarCota();
                }
            })
            .finally(() => {
                // Restaurar o estado disabled se necessário
                if (wasDisabled) {
                    paginasInput.disabled = true;
                }
            });
    });

    // --- AJUSTE NO EVENTO DE SOLICITAR BALCÃO ---
    document.getElementById('solicitar_balcao').addEventListener('change', function () {
        const upload = document.getElementById('arquivo');
        const paginas = document.getElementById('qtd_paginas');
        upload.disabled = this.checked;
        paginas.disabled = this.checked;
        this.checked ? upload.removeAttribute('required') : upload.setAttribute('required', 'required');
        paginas.value = this.checked ? 1 : '';
        if (this.checked) {
            showOnPageToast('Solicitação no balcão selecionada. Número de páginas fixado em 1.');
            paginas.dispatchEvent(new Event('input')); // Disparar evento input para atualizar o preview
        }
        atualizarPreview();
    });

    // --- PREVIEW DE CÓPIAS E PÁGINAS ---
    const qtdCopiasInput = document.getElementById('qtd_copias');
    const qtdPaginasInput = document.getElementById('qtd_paginas');
    const previewDiv = document.getElementById('preview-copias-paginas');

    function atualizarPreview() {
        const copias = parseInt(qtdCopiasInput.value, 10) || 0;
        const paginas = parseInt(qtdPaginasInput.value, 10) || 0;
        if (copias > 0 && paginas > 0) {
            previewDiv.innerHTML = `<p>Total: ${copias} cópias x ${paginas} páginas = ${copias * paginas} impressões. <i class="fas fa-info-circle" title="Cada cópia conta como uma impressão separada."></i></p>`;
        } else {
            previewDiv.innerHTML = '';
        }
    }

    qtdCopiasInput.addEventListener('input', atualizarPreview);
    qtdPaginasInput.addEventListener('input', atualizarPreview);

    // Cargas iniciais e verificação periódica
    carregarCota();
    carregarSolicitacoes();
    setInterval(verificarNotificacoes, 15000);
});