<?php
// Dashboard do Servidor
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'servidor') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_servidor.css">
<main class="container">
  <h1>Painel do Servidor</h1>
  <div id="cota-info" style="margin-bottom:1em;font-weight:bold;color:#1a4b2a;"></div>
  <form id="form-solicitacao" enctype="multipart/form-data">
    <label>Arquivo para impressão
      <input type="file" name="arquivo" required accept=".pdf,.doc,.docx,.jpg,.png">
    </label>
    <label>Quantidade de cópias
      <input type="number" name="qtd_copias" min="1" max="100" required>
    </label>
    <label>
      <input type="checkbox" name="colorida" value="1"> Impressão colorida
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
// Buscar cotas disponíveis do servidor
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
carregarCota();

// Envio AJAX do formulário
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
    if(data.sucesso) {
      form.reset();
      carregarSolicitacoes();
      carregarCota();
    }
  })
  .catch(() => alert('Erro ao enviar solicitação.'));
});

// Carregar solicitações recentes via AJAX
function carregarSolicitacoes() {
  fetch('listar_solicitacoes.php')
    .then(r => r.json())
    .then(data => {
      let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>Arquivo</th><th>Cópias</th><th>Colorida</th><th>Status</th><th>Data</th></tr></thead><tbody>';
      if(data.length === 0) html += '<tr><td colspan="5">Nenhuma solicitação recente.</td></tr>';
      else data.forEach(s => {
        html += `<tr>
          <td>${s.arquivo}</td>
          <td>${s.qtd_copias}</td>
          <td>${s.colorida == 1 ? 'Sim' : 'Não'}</td>
          <td>${s.status}</td>
          <td>${s.data}</td>
        </tr>`;
      });
      html += '</tbody></table>';
      document.getElementById('tabela-solicitacoes').innerHTML = html;
    });
}
carregarSolicitacoes();

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

const inputArquivo = document.querySelector('input[name="arquivo"]');
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
</script>
<?php require_once '../../includes/footer.php'; ?>
