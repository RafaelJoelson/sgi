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
<div class="dashboard-layout">
  <aside class="dashboard-aside">
    <h2>Menu</h2>
    <nav>
      <a href="dashboard_reprografo.php" class="active">Solicitações Pendentes</a>
      <a href="relatorio_reprografo.php">Relatórios</a>
      <a href="../../includes/logout.php">Sair</a>
    </nav>
  </aside>
  <main class="dashboard-main">
    <h1>Painel do Reprográfo</h1>
    <section id="solicitacoes-pendentes">
      <h2>Solicitações Pendentes</h2>
      <div id="tabela-solicitacoes"></div>
      <button id="btn-explorar-uploads" style="margin-top:1em;">Explorar Arquivos da Pasta Uploads</button>
      <div id="lista-uploads" style="margin-top:1em;"></div>
    </section>
  </main>
</div>
<script>
let ultimosIds = [];
function carregarSolicitacoes(notify = false) {
  fetch('listar_solicitacoes_pendentes.php')
    .then(r => r.json())
    .then(data => {
      console.log('Solicitações recebidas:', data); // DEBUG: Verificar se coloridas aparecem
      let html = '<table style="width:100%;font-size:0.98em;"><thead><tr><th>Arquivo</th><th>Solicitante</th><th>Cópias</th><th>Páginas</th><th>Colorida</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead><tbody>';
      if(data.length === 0) html += '<tr><td colspan="8">Nenhuma solicitação pendente.</td></tr>';
      else data.forEach(s => {
        html += `<tr>
          <td><a href="../uploads/${s.arquivo}" target="_blank">${s.arquivo}</a></td>
          <td>${s.nome_solicitante}</td>
          <td>${s.qtd_copias}</td>
          <td><input type="number" min="1" max="500" value="${s.qtd_paginas}" style="width:60px" onchange="editarPaginas(${s.id}, this.value)"></td>
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
      // Notificação de novas solicitações
      const ids = data.map(s => s.id);
      if (notify && ultimosIds.length > 0) {
        const novos = ids.filter(id => !ultimosIds.includes(id));
        if (novos.length > 0) {
          if (window.Notification && Notification.permission === 'granted') {
            new Notification('Nova solicitação de impressão recebida!');
          } else if (window.Notification && Notification.permission !== 'denied') {
            Notification.requestPermission();
          } else {
            alert('Nova solicitação de impressão recebida!');
          }
        }
      }
      ultimosIds = ids;
    });
}
carregarSolicitacoes();
setInterval(() => carregarSolicitacoes(true), 10000); // Checa a cada 10s

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

function editarPaginas(id, valor) {
  fetch('editar_paginas.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `id=${id}&qtd_paginas=${valor}`
  })
  .then(r => r.json())
  .then(data => {
    if(!data.sucesso) alert(data.mensagem);
  })
  .catch(() => alert('Erro ao atualizar número de páginas.'));
}

document.getElementById('btn-explorar-uploads').addEventListener('click', function() {
  const listaUploads = document.getElementById('lista-uploads');
  listaUploads.innerHTML = '<em>Carregando arquivos...</em>';
  fetch('excluir_uploads.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'acao=listar'
  })
  .then(r => r.json())
  .then(data => {
    if (!data.sucesso) {
      listaUploads.innerHTML = '<em>Não foi possível listar os arquivos.</em>';
      return;
    }
    const arquivos = data.arquivos;
    if (!arquivos || arquivos.length === 0) {
      listaUploads.innerHTML = '<em>Nenhum arquivo encontrado.</em>';
      return;
    }
    let lista = `<form id=\"form-excluir-uploads\"><ul style=\"list-style:none;padding:0;\">` +
      arquivos.map(f => `<li><label><input type=\"checkbox\" name=\"arquivos[]\" value=\"${encodeURIComponent(f)}\"> <a href=\"../../uploads/${encodeURIComponent(f)}\" target=\"_blank\">${f}</a></label></li>`).join('') +
      `</ul><button type=\"submit\" style=\"margin-top:1em;\">Excluir Selecionados</button> <button type=\"button\" id=\"btn-selecionar-todos\">Selecionar Todos</button></form>`;
    listaUploads.innerHTML = lista;
    // Selecionar todos
    document.getElementById('btn-selecionar-todos').onclick = function() {
      document.querySelectorAll('#form-excluir-uploads input[type=checkbox]').forEach(cb => cb.checked = true);
    };
    // Submissão do form
    document.getElementById('form-excluir-uploads').onsubmit = function(e) {
      e.preventDefault();
      const selecionados = Array.from(document.querySelectorAll('#form-excluir-uploads input[type=checkbox]:checked')).map(cb => cb.value);
      if (selecionados.length === 0) {
        alert('Selecione ao menos um arquivo para excluir.');
        return;
      }
      if (!confirm('Confirma a exclusão dos arquivos selecionados?')) return;
      listaUploads.innerHTML = '<em>Excluindo arquivos...</em>';
      fetch('excluir_uploads.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'acao=excluir&arquivos=' + encodeURIComponent(JSON.stringify(selecionados))
      })
      .then(r => r.json())
      .then(resp => {
        if (resp.sucesso) {
          listaUploads.innerHTML = `<span style=\"color:green;\">${resp.mensagem}</span>`;
          setTimeout(() => document.getElementById('btn-explorar-uploads').click(), 1200);
        } else {
          listaUploads.innerHTML = `<span style=\"color:red;\">${resp.mensagem || 'Erro ao excluir.'}</span>`;
        }
      })
      .catch(() => {
        listaUploads.innerHTML = '<span style=\"color:red;\">Erro ao excluir arquivos.</span>';
      });
    };
  })
  .catch(() => {
    listaUploads.innerHTML = '<em>Não foi possível listar os arquivos.</em>';
  });
});
</script>
<?php require_once '../../includes/footer.php'; ?>
