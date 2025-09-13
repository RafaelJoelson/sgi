<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Solicitações - Servidor</title>
    <link rel="stylesheet" href="../../styles.css">
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://kit.fontawesome.com/0e8e8e8e8e.js" crossorigin="anonymous"></script>
</head>
<body>
    <main class="container">
        <h2>Histórico de Solicitações</h2>
        <table id="tabela-historico" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Arquivo</th>
                    <th>Cópias</th>
                    <th>Páginas</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <!-- Preenchido via JS -->
            </tbody>
        </table>
    </main>
    <script src="js/dashboard_servidor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof carregarHistoricoSolicitacoes === 'function') {
                carregarHistoricoSolicitacoes();
            }
        });
    </script>
</body>
</html>
