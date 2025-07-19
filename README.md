# SGI - Sistema de Gestão de Impressão Acadêmica V.1.5.3 (alpha)

Projeto de extensão do Instituto Federal do Sudeste de Minas Gerais - Campus São João del-Rei.

Este sistema tem como objetivo ser implementado no campus para gerenciar cotas de impressão acadêmica, promovendo controle, transparência e automação de processos institucionais.

**Desenvolvido por alunos do curso de Gestão em Tecnologia da Informação (GTI).**

- **Coordenadora:** GILMA APARECIDA S. CAMPOS
- **Alunos:**

  - RAFAEL JOELSON DA SILVA
  - IASMIN DANIELE DE OLIVEIRA
  - LIDIANE CONCEIÇÃO DE ANDRADE
  - EMILY CAMPOS COLONELLI


## Funcionalidades Principais

- Gestão de Alunos, Servidores e Reprográfo
- Controle de cotas por turma, aluno e servidor
- Dashboards distintos para CAD, COEN, Aluno, Servidor e Reprográfo
- Configuração e log de semestres letivos
- Exportação de logs em CSV
- Reset automático de cotas por semestre
- Permissões e acessos por setor
- Dupla confirmação em ações críticas

## Estrutura de Pastas

```
includes/           # Configurações, header, footer, login
pages/
  admin_cad/        # Painel e ações do setor CAD
  admin_coen/       # Painel e ações do setor COEN
  aluno/            # Painel do aluno
  servidor/         # Painel do servidor
  reprografo/       # Painel do reprográfo
  utils/            # Manuais do sistema e página de Suporte
img/                # Imagens do sistema
styles.css          # Estilos globais
print_base.css      # Estilos globais e impressão
favicon.ico         # Íncone do sistema
sgi_bd.sql          # Script do banco de dados
```

## Perfis e Dashboards

- **Aluno:** Visualiza cotas, solicitações e dados pessoais
- **Servidor:** Visualiza e negocia cotas, solicitações
- **CAD:** Gerencia turmas, alunos, cotas e semestre letivo
- **COEN:** Gerencia servidores, cotas e semestre letivo
- **Reprográfo:** Gerencia solicitações de impressão

## Banco de Dados

- Modelagem relacional com tabelas: Curso, Turma, Aluno, Servidor, CotaAluno, CotaServidor, SemestreLetivo, LogSemestreLetivo
- Eventos automáticos para reset de cotas, desativação e limpeza

## Instalação

1. Clone o repositório para o diretório do seu servidor web (ex: `c:/xampp/htdocs/sgi`)
2. Importe o arquivo `sgi_bd.sql` no seu MySQL
3. Configure o acesso ao banco em `includes/config.php`
4. Acesse `http://localhost/sgi` no navegador

## Requisitos
- PHP 7.4+
- MySQL 5.7+
- Servidor web (Apache recomendado)

## Observações
- O sistema implementa regras institucionais para datas de semestre, cotas e permissões.
- Logs de alterações de semestre podem ser exportados em CSV.
- Ações críticas possuem dupla confirmação para segurança.

## Licença
Projeto acadêmico. Uso institucional e educacional.

# Limpeza automática da pasta uploads (arquivos antigos)

Para manter a pasta `uploads` limpa e evitar acúmulo de arquivos antigos, recomenda-se agendar a execução de um script PHP que remove arquivos com mais de 15 dias.

## Como agendar a execução

### Linux (cron)
1. Dê permissão de execução ao script, se necessário.
2. Edite o crontab:
   ```sh
   crontab -e
   ```
3. Adicione a linha (ajuste o caminho do PHP e do script):
   ```sh
   0 2 * * * /usr/bin/php /includes/tarefas_diarias.php
   ```
   Isso executa diariamente às 2h da manhã.

### Windows (Agendador de Tarefas)
1. Abra o Agendador de Tarefas do Windows.
2. Crie uma nova tarefa básica.
3. Defina a frequência (diária).
4. Na ação, escolha "Iniciar um programa" e aponte para o executável do PHP, por exemplo:
   - Programa/script: `C:\xampp\php\php.exe`
   - Adicionar argumentos: `C:\xampp\htdocs\sgi\includes\tarefas_diarias.phpp`
5. Conclua a configuração.

---

## Ativando eventos automáticos no MySQL

Para que os eventos de limpeza e reset de cotas funcionem, é necessário ativar o event scheduler do MySQL. Execute o comando abaixo no seu MySQL:

```sql
SET GLOBAL event_scheduler = ON;
```

Você pode executar esse comando via phpMyAdmin, MySQL Workbench ou terminal.

> Dica: Para garantir que o event scheduler sempre inicie ativado, adicione a linha abaixo no arquivo de configuração do MySQL (`my.cnf` ou `my.ini`):
> 
> ```ini
> event_scheduler=ON
> ```

---

> **Atenção:**
> - O script remove apenas arquivos físicos da pasta uploads. Se quiser remover também registros órfãos no banco, adapte conforme sua necessidade.
> - Certifique-se de que o usuário do sistema (Linux ou Windows) tenha permissão de escrita na pasta uploads.
