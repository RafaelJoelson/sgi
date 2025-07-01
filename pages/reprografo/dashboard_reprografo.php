<?php
// Dashboard do Reprográfo
require_once '../../includes/config.php';
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] !== 'reprografo') {
    header('Location: ../../index.php');
    exit;
}
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_reprografo.css">
<main class="container">
  <h1>Painel do Reprográfo</h1>
  <section id="solicitacoes-pendentes">
    <h2>Solicitações Pendentes</h2>
    <div id="tabela-solicitacoes"></div>
  </section>
</main>
<script>
function carregarSolicitacoes() {
  fetch('listar_solicitacoes_pendentes.php')
    .then(r => r.json())
    .then(data => {
      let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>Arquivo</th><th>Solicitante</th><th>Cópias</th><th>Colorida</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead><tbody>';
      if(data.length === 0) html += '<tr><td colspan="7">Nenhuma solicitação pendente.</td></tr>';
      else data.forEach(s => {
        html += `<tr>
          <td><a href="../uploads/${s.arquivo}" target="_blank">${s.arquivo}</a></td>
          <td>${s.nome_solicitante}</td>
          <td>${s.qtd_copias}</td>
          <td>${s.colorida == 1 ? 'Sim' : 'Não'}</td>
          <td>${s.status}</td>
          <td>${s.data}</td>
          <td>
            <button onclick="atualizarStatus(${s.id},'Aceita')">Aceitar</button>
            <button onclick="atualizarStatus(${s.id},'Rejeitada')">Rejeitar</button>
          </td>
        </tr>`;
      });
      html += '</tbody></table>';
      document.getElementById('tabela-solicitacoes').innerHTML = html;
    });
}
carregarSolicitacoes();

function atualizarStatus(id, status) {
  if(!confirm('Confirma a ação?')) return;
  fetch('atualizar_status_solicitacao.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `id=${id}&status=${status}`
  })
  .then(r => r.json())
  .then(data => {
    alert(data.mensagem);
    if(data.sucesso) carregarSolicitacoes();
  })
  .catch(() => alert('Erro ao atualizar status.'));
}
</script>
<?php require_once '../../includes/footer.php'; ?>
