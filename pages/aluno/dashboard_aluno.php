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
  <h1>Painel do Aluno</h1>
  <div id="cota-info" style="margin-bottom:1em;font-weight:bold;color:#1a4b2a;"></div>
  <form id="form-solicitacao" enctype="multipart/form-data">
    <label>Arquivo para impressão
      <input type="file" name="arquivo" id="arquivo" required accept=".pdf,.doc,.docx,.jpg,.png">
    </label>
    <label>Quantidade de cópias
      <input type="number" name="qtd_copias" min="1" max="100" required>
    </label>
    <label>Número de páginas
      <input type="number" name="qtd_paginas" id="qtd_paginas" min="1" max="500" required>
    </label>
    <button type="submit">Enviar Solicitação</button>
  </form>

  <section id="status-solicitacoes">
    <h2>Minhas Solicitações Recentes</h2>
    <div id="tabela-solicitacoes"></div>
  </section>

  <button onclick="window.location.href='historico_solicitacoes.php'">Ver Histórico Completo</button>
</main>
<script>
// Buscar cota disponível do aluno
function carregarCota() {
  fetch('cota_aluno.php')
    .then(r => r.json())
    .then(data => {
      if(data.sucesso) {
        document.getElementById('cota-info').innerText = `Cota disponível: ${data.cota_disponivel} páginas`;
      } else {
        document.getElementById('cota-info').innerText = 'Não foi possível obter a cota.';
      }
    });
}
carregarCota();

// Função para contar páginas de PDF (simples, client-side)
function contarPaginasPDF(file, callback) {
  const reader = new FileReader();
  reader.onload = function() {
    const texto = reader.result;
    const matches = (texto.match(/\/Type\s*\/Page[^s]/g) || []).length;
    callback(matches > 0 ? matches : 1);
  };
  reader.readAsText(file);
}

const inputArquivo = document.getElementById('arquivo');
const inputPaginas = document.getElementById('qtd_paginas');
inputArquivo.addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const ext = file.name.split('.').pop().toLowerCase();
  if (ext === 'pdf') {
    contarPaginasPDF(file, function(paginas) {
      inputPaginas.value = paginas;
      inputPaginas.readOnly = true;
    });
  } else if (['jpg','jpeg','png'].includes(ext)) {
    inputPaginas.value = 1;
    inputPaginas.readOnly = true;
  } else {
    inputPaginas.value = '';
    inputPaginas.readOnly = false;
    inputPaginas.placeholder = 'Informe o número de páginas';
  }
});

// Envio AJAX do formulário
const form = document.getElementById('form-solicitacao');
form.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(form);
  fetch('enviar_solicitacao.php', {
    method: 'POST',
    body: formData
  })
  .then(async r => {
    const text = await r.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      alert('Erro inesperado: ' + text);
      throw e;
    }
    alert(data.mensagem);
    if(data.sucesso) {
      form.reset();
      carregarSolicitacoes();
      carregarCota();
    }
  })
  .catch(err => {
    alert('Erro ao enviar solicitação. ' + (err && err.message ? err.message : ''));
  });
});

let ultimosStatus = {};
// Carregar solicitações recentes via AJAX
function carregarSolicitacoes(notify = false) {
  fetch('listar_solicitacoes.php')
    .then(r => r.json())
    .then(data => {
      let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>Arquivo</th><th>Cópias</th><th>Status</th><th>Data</th></tr></thead><tbody>';
      if(data.length === 0) html += '<tr><td colspan="4">Nenhuma solicitação recente.</td></tr>';
      else data.forEach(s => {
        // Adiciona link para download se houver arquivo
        let linkArquivo = s.arquivo ? `<a href="../../uploads/${encodeURIComponent(s.arquivo)}" target="_blank" rel="noopener" download>${s.arquivo}</a>` : '-';
        html += `<tr>
          <td>${linkArquivo}</td>
          <td>${s.qtd_copias}</td>
          <td>${s.status}</td>
          <td>${s.data}</td>
        </tr>`;
      });
      html += '</tbody></table>';
      document.getElementById('tabela-solicitacoes').innerHTML = html;
      // Notificação de mudança de status
      if (notify) {
        data.forEach(s => {
          if (ultimosStatus[s.id] && ultimosStatus[s.id] !== s.status) {
            const mensagem = `Sua solicitação "${s.arquivo}" foi atualizada para: ${s.status}`;
            if (window.Notification) {
              if (Notification.permission === 'granted') {
                new Notification(mensagem);
              } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                  if (permission === 'granted') {
                    new Notification(mensagem);
                  } else {
                    alert(mensagem);
                  }
                });
              } else {
                alert(mensagem);
              }
            } else {
              alert(mensagem);
            }
          }
          ultimosStatus[s.id] = s.status;
        });
      } else {
        data.forEach(s => { ultimosStatus[s.id] = s.status; });
      }
    });
}
carregarSolicitacoes();
setInterval(() => carregarSolicitacoes(true), 10000); // Checa a cada 10s
</script>
<?php require_once '../../includes/footer.php'; ?>
