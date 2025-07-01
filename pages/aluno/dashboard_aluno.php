<?php
// Dashboard do Aluno
session_start();
require_once '../../includes/header.php';
?>
<link rel="stylesheet" href="dashboard_aluno.css">
<main class="container">
  <h1>Painel do Aluno</h1>
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
    <button type="submit">Enviar Solicitação</button>
  </form>

  <section id="status-solicitacoes">
    <h2>Minhas Solicitações Recentes</h2>
    <div id="tabela-solicitacoes"></div>
  </section>

  <button onclick="window.location.href='historico_solicitacoes.php'">Ver Histórico Completo</button>
</main>
<script>
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
</script>
<?php require_once '../../includes/footer.php'; ?>
