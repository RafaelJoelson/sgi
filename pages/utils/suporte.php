<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte - Sistema de Gerenciamento de Impressão</title>
    <!-- Incluindo Font Awesome para os ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="suporte.css">
</head>
<body>

    <div class="container">
        <header class="header-suporte">
            <h1>Central de Ajuda</h1>
            <p>Encontre respostas para suas dúvidas e entre em contato conosco.</p>
        </header>

        <!-- Seção de Perguntas Frequentes (FAQ) -->
        <section class="secao-suporte">
            <h2><i class="fas fa-question-circle"></i> Perguntas Frequentes (FAQ)</h2>
            <div class="faq-container">
                <details class="faq-item">
                    <summary>Como faço para solicitar uma impressão?</summary>
                    <div class="faq-resposta">
                        <p>Acesse seu painel (de aluno ou servidor), preencha o formulário de solicitação, anexe o arquivo (se necessário) e clique em "Enviar Solicitação". Você pode acompanhar o status do seu pedido na mesma página.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>Qual é a minha cota de impressão?</summary>
                    <div class="faq-resposta">
                        <p><strong>Alunos:</strong> A cota é compartilhada por turma e é reiniciada a cada semestre letivo. O valor disponível é exibido no seu painel.</p>
                        <p><strong>Servidores:</strong> Cada servidor possui uma cota individual para impressões P&B e coloridas, que também é reiniciada a cada semestre. O saldo é exibido no seu painel.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>Esqueci minha senha. O que eu faço?</summary>
                    <div class="faq-resposta">
                        <p>Você deve entrar em contato com o setor responsável pelo seu cadastro para solicitar a redefinição de sua senha. Alunos devem procurar o CAD (Coordenação de Apoio ao Discente) e Servidores devem procurar o COEN (Coordenação de Ensino).</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>O que significa "Solicitação no Balcão"?</summary>
                    <div class="faq-resposta">
                        <p>Esta opção é para quando você já tem o material físico em mãos (como um livro ou uma folha) e precisa apenas de uma cópia. Ao marcar esta opção, você não precisa enviar um arquivo, apenas informar a quantidade de cópias e páginas. A reprografia entenderá que você levará o material original até eles.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>Posso solicitar impressões coloridas?</summary>
                    <div class="faq-resposta">
                        <p>Apenas **servidores** têm uma cota específica para impressões coloridas e podem solicitá-las através do sistema. Alunos, por padrão, só podem solicitar impressões em preto e branco.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>(Para Reprógrafo) Como sei quando chega um novo pedido?</summary>
                    <div class="faq-resposta">
                        <p>O sistema foi projetado para te notificar de três formas: com um alerta sonoro, fazendo a aba do navegador piscar e exibindo uma notificação no canto da tela. Para que isso funcione, você precisa permitir que o site envie notificações quando o navegador perguntar.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>(Para Admin) Como funciona o reset de cotas?</summary>
                    <div class="faq-resposta">
                        <p>O reset de cotas é uma tarefa automática. No primeiro dia de cada semestre letivo cadastrado em "Configurar Semestre", o sistema irá zerar as cotas usadas e redefinir os totais para os valores padrão configurados em "Definir Cotas Padrão".</p>
                    </div>
                </details>
            </div>
        </section>

        <!-- Seção de Manuais -->
        <section class="secao-suporte">
            <h2><i class="fas fa-book"></i> Manuais e Documentos</h2>
            <ul class="lista-manuais">
                <li><a href="#" download><i class="fas fa-file-pdf"></i> Manual de Uso do Sistema para Alunos</a></li>
                <li><a href="#" download><i class="fas fa-file-pdf"></i> Manual de Uso do Sistema para Servidores</a></li>
                <li><a href="#" download><i class="fas fa-file-pdf"></i> Manual de Uso do Sistema para a Reprografia</a></li>
                <li><a href="#" download><i class="fas fa-file-alt"></i> Política de Uso da Reprografia</a></li>
            </ul>
        </section>

        <!-- Seção de Contato -->
        <section class="secao-suporte">
            <h2><i class="fas fa-envelope"></i> Contato</h2>
            <ul class="contato-info">
                <li>
                    <p>Para problemas técnicos, dúvidas ou sugestões, entre em contato com o suporte de TI através do e-mail:</p>
                    <a href="mailto:ti.sjdr@ifsudestemg.edu.br"><i class="fas fa-at"></i> ti.sjdr@ifsudestemg.edu.br</a>
                </li>
                <li>
                    <p>Ou entre em contato com o desenvolvedor do sistema através do e-mail:</p>
                    <a href="mailto:ti.rafaeljoelsonifsudeste@gmail.com"><i class="fas fa-at"></i> rafaeljoelsonifsudeste@gmail.com</a>
                </li>
            </ul>
        </section>

        <div style="text-align: center;">
            <a href="javascript:history.back()" class="btn-voltar">&larr; Voltar</a>
        </div>

    </div>

</body>
</html>
