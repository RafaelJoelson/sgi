<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte - Sistema de Gerenciamento de Impressão</title>
    <!-- Incluindo Font Awesome para os ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos gerais da página de suporte */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap');

        :root {
            --cor-primaria: #0056b3;
            --cor-fundo: #f4f7f6;
            --cor-superficie: #ffffff;
            --cor-texto: #212529;
            --cor-texto-suave: #6c757d;
            --cor-borda: #dee2e6;
            --sombra-card: 0 4px 12px rgba(0, 0, 0, 0.07);
            --raio-borda: 8px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--cor-fundo);
            color: var(--cor-texto);
            line-height: 1.7;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .header-suporte {
            text-align: center;
            margin-bottom: 3rem;
            border-bottom: 1px solid var(--cor-borda);
            padding-bottom: 1.5rem;
        }

        .header-suporte h1 {
            font-size: 2.5rem;
            color: var(--cor-primaria);
            margin-bottom: 0.5rem;
        }

        .header-suporte p {
            font-size: 1.1rem;
            color: var(--cor-texto-suave);
        }

        .secao-suporte {
            background-color: var(--cor-superficie);
            padding: 2rem;
            border-radius: var(--raio-borda);
            box-shadow: var(--sombra-card);
            margin-bottom: 2rem;
        }

        .secao-suporte h2 {
            font-size: 1.8rem;
            color: var(--cor-primaria);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Estilos para o FAQ (Perguntas Frequentes) */
        .faq-item {
            border-bottom: 1px solid var(--cor-borda);
            padding: 1rem 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-item summary {
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            list-style: none; /* Remove o marcador padrão */
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-item summary::-webkit-details-marker {
            display: none; /* Remove o marcador no Chrome/Safari */
        }

        .faq-item summary::after {
            content: '\f078'; /* Ícone de seta para baixo do Font Awesome */
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            transition: transform 0.3s ease-in-out;
        }

        .faq-item[open] summary::after {
            transform: rotate(180deg);
        }

        .faq-resposta {
            padding: 1rem 0 0 1rem;
            color: var(--cor-texto-suave);
            border-left: 3px solid var(--cor-primaria);
            margin-top: 1rem;
        }

        /* Estilos para Manuais e Contato */
        .lista-manuais, .contato-info {
            list-style: none;
            padding: 0;
        }

        .lista-manuais li, .contato-info li {
            margin-bottom: 1rem;
        }

        .lista-manuais a, .contato-info a {
            color: var(--cor-primaria);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .lista-manuais a:hover, .contato-info a:hover {
            text-decoration: underline;
            color: #00418a;
        }

        .btn-voltar {
            display: inline-block;
            text-align: center;
            margin-top: 2rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--cor-texto-suave);
            color: white;
            text-decoration: none;
            border-radius: var(--raio-borda);
            transition: background-color 0.2s;
        }

        .btn-voltar:hover {
            background-color: #5a6268;
        }

    </style>
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
