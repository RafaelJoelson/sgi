Aqui está o conteúdo formatado do `README.md` em **Markdown** moderno, com seções bem estruturadas, cabeçalhos claros e elementos visuais que facilitam a leitura:

---

```markdown
# SGI - Sistema de Gestão de Impressão Acadêmica V.2.2 (Beta)

**Projeto de extensão do Instituto Federal do Sudeste de Minas Gerais - Campus São João del-Rei**

Este sistema tem como objetivo ser implementado no campus para gerenciar cotas de impressão acadêmica, promovendo **controle**, **transparência** e **automação** de processos institucionais.

> Desenvolvido por alunos do curso de Gestão em Tecnologia da Informação (GTI).

---

## 👩‍🏫 Coordenação

- **Gilma Aparecida Santos Campos**

## 👨‍💻 Alunos Desenvolvedores

- **Rafael Joelson da Silva**  
- **Iasmin Daniele de Oliveira**  
- **Lidiane Conceição de Andrade**  
- **Emily Campos Colonelli**

---

## ⚙️ Funcionalidades Principais

- **Gestão de Usuários**  
  Perfis distintos para Alunos, Servidores e Reprografia.

- **Controle de Cotas**  
  Gerenciamento de cotas para turmas e servidores com valores padrão configuráveis.

- **Painéis Administrativos**  
  Dashboards separados para os setores **CAD** e **COEN**, com permissões específicas.

- **Relatórios Dinâmicos**  
  Geração de relatórios de consumo com filtros flexíveis e exportação para PDF (biblioteca **Dompdf**).

- **Automação de Tarefas**  
  Script `tarefas_diarias.php` para:
  - Desativar usuários inativos
  - Arquivar solicitações antigas
  - Resetar cotas semestralmente

- **Notificações em Tempo Real**  
  Sistema de notificações com som e alerta visual no navegador para todos os perfis.

- **Página de Suporte**  
  Central de ajuda com FAQ e manuais para download.

---

## 📁 Estrutura de Pastas

```

/
├── includes/              # Configurações, header, footer, scripts de login
├── pages/
│   ├── admin\_cad/         # Painel e ações do setor CAD
│   ├── admin\_coen/        # Painel e ações do setor COEN
│   ├── aluno/             # Painel e ações do Aluno
│   ├── servidor/          # Painel e ações do Servidor
│   ├── reprografia/       # Painel e ações da Reprografia
│   ├── erros/             # Páginas de erros web.
│   └── utils/             # Página de suporte e manuais
│       └── documents/     # Manuais em PDF
├── uploads/               # Arquivos enviados para impressão
├── vendor/                # Bibliotecas do Composer (ex: dompdf)
├── css/                   # Folhas de estilo
├── img/                   # Imagens do sistema
├── index.php              # Login principal (Alunos e Servidores)
├── reprografia.php        # Login exclusivo da Reprografia
├── suporte.php            # Página de ajuda e FAQ
└── .htaccess              # Regras de URL e segurança

````

---

## 👥 Perfis e Dashboards

- **Aluno**  
  Solicita impressões, visualiza a cota da turma e acompanha o status.

- **Servidor**  
  Solicita impressões P&B ou coloridas e acompanha seus pedidos.

- **Reprografia**  
  Gerencia a fila de solicitações, aceitando ou rejeitando pedidos.

- **Admin (CAD)**  
  Gerencia alunos, turmas, cursos e cotas acadêmicas.

- **Admin (COEN)**  
  Gerencia servidores e suas cotas.

- **Admin Geral**  
  Acesso aos setores CAD e COEN com permissões para configurar o semestre letivo e padrões de cotas.

---

## 🧰 Instalação

1. Clone o repositório para o diretório do seu servidor web:

```bash
git clone https://github.com/seu-usuario/sgi.git
````

2. Instale as dependências com o Composer:

```bash
composer require dompdf/dompdf
```

3. Importe o banco de dados:
   Use o arquivo `sgi_bd.sql` no seu MySQL/MariaDB.

4. Ajuste as credenciais do banco em:

```
includes/config.php
```

5. Certifique-se de que o `mod_rewrite` do Apache está ativado.

6. Acesse pelo navegador:
   `http://localhost/sgi` ou o URL correspondente.

---

## 🔧 Requisitos

* PHP **7.4+** (com extensão **PDO** para MySQL)
* MySQL **5.7+** ou **MariaDB**
* **Composer** para gerenciamento de dependências
* Servidor web com suporte a `.htaccess` (**Apache** recomendado)

---

## ⏱️ Automação de Tarefas (Cron Job)

Para o funcionamento ideal, o script `tarefas_diarias.php` deve ser executado diariamente via **Cron Job**.

**Script a ser executado:**

```
[path_do_projeto]/backend/tarefas_diarias.php
```

> ⚠️ A execução regular deste script é **essencial** para o funcionamento do sistema.

---

## 📄 Licença

Projeto **acadêmico**. Uso **institucional e educacional** autorizado.
