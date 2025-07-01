# SGI - Sistema de Gestão de Impressão Acadêmica

Projeto de extensão do Instituto Federal do Sudeste de Minas Gerais - Campus São João del-Rei.

Este sistema tem como objetivo ser implementado no campus para gerenciar cotas de impressão acadêmica, promovendo controle, transparência e automação de processos institucionais.

**Desenvolvido por alunos do curso de Gestão em Tecnologia da Informação (GTI).**

- **Coordenadora:** GILMA APARECIDA S. CAMPOS
- **Alunos:**

  - RAFAEL JOELSON DA SILVA - GESTOR DE PROJETO E DESENVOLVEDOR WEB
  - IASMIN DANIELE DE OLIVEIRA - ANALISTA DE SOFTWARE
  - LIDIANE CONCEIÇÃO DE ANDRADE - ANALISTA DE SOFTWARE


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
img/                # Imagens do sistema
styles.css          # Estilos globais
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
