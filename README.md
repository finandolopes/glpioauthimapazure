# GLPI OAuth IMAP Azure Plugin

**Autor:** Fernando Lopes

## Visão Geral
Plugin avançado para GLPI 10 que integra autenticação OAuth2 (Azure AD), coleta/envio de e-mails via IMAP/SMTP, multi-conta, logs detalhados, auditoria, assistente de configuração, fila de processamento e permissões administrativas.

## Funcionalidades
- Autenticação OAuth2 (Azure AD)
- Coleta e envio de e-mails IMAP/SMTP
- Multi-conta: suporte a múltiplas caixas/contas Azure
- Gestão de anexos: download seguro e destaque nos logs
- Logs detalhados: tela com filtros, exportação CSV, destaque visual
- Logs de auditoria: registro de todas as ações administrativas
- Assistente de configuração: passo a passo com testes automáticos
- Fila de processamento: visualização e controle de e-mails pendentes
- Permissões: acesso restrito a administradores GLPI
- Internacionalização: pt_BR e en_US

## Instalação
1. Copie a pasta do plugin para o diretório de plugins do GLPI.
2. Acesse o GLPI como administrador e ative o plugin.
3. Siga o assistente de configuração no menu do plugin.

## Estrutura de Arquivos
- `inc/`: classes de configuração, IMAP, OAuth, log, auditoria, fila
- `front/`: telas (logs, config, contas, fila, assistente, auditoria)
- `views/menu.php`: menu do plugin
- `locales/`: arquivos de idioma
- `setup.php`: criação de tabelas
- `logs/attachments/`: anexos salvos

## Segurança
- Todas as telas sensíveis exigem perfil de administrador GLPI.
- Todos os dados de entrada/saída são escapados.
- Exportação CSV protegida contra injeção.
- Tokens sensíveis criptografados.

## Auditoria e Logs
- Toda ação administrativa relevante é registrada.
- Filtros e exportações de logs são auditados.
- Logs e auditoria possuem paginação e exportação.

## Assistente de Configuração
- Valida pré-requisitos do PHP.
- Garante cadastro de contas.
- Testa OAuth2 e IMAP automaticamente.
- Feedback visual em cada etapa.

## Fila de Processamento
- E-mails coletados são armazenados para processamento assíncrono.
- Tela de fila exibe status, erro e detalhes de cada item.


## Compatibilidade com GLPI 9.5.5 (Legacy)

Este plugin foi desenvolvido e testado principalmente para GLPI 10.x. É possível instalar e utilizar em GLPI 9.5.5, porém com as seguintes limitações e riscos:

- **Interface:** Recursos visuais modernos (tabelas responsivas, ícones, temas escuros) podem não funcionar corretamente.
- **APIs e Integração:** Algumas APIs, métodos de hook e integração podem não estar disponíveis ou apresentar comportamento diferente.
- **Segurança:** Funcionalidades de CSRF, sessões e proteção de formulários são menos robustas em versões antigas.
- **Plugins/Hooks:** O suporte a plugins e hooks pode ser limitado ou exigir adaptações manuais.
- **Testes:** Não há garantia de funcionamento pleno em GLPI < 10. Testes automatizados e integração contínua não cobrem versões antigas.
- **Recomendação:** Sempre que possível, atualize o GLPI para a versão 10.x ou superior para melhor experiência, segurança e suporte.

Ao instalar em GLPI 9.5.5, o plugin exibirá um alerta e exigirá confirmação manual do administrador.

---

**Créditos:**
Plugin desenvolvido por Fernando Lopes.# Plugin GLPI OAuth IMAP Azure

Este plugin implementa autenticação IMAP OAuth2 com Azure (Microsoft 365) para o GLPI 10, coleta/envio de e-mails, tratamento de erros e tela administrativa para logs.

## Estrutura
- `setup.php`, `manifest.xml`, `hook.php`: arquivos principais do plugin
- `inc/`: classes de configuração, OAuth, IMAP, logs
- `front/`: telas administrativas (configuração, logs)
- `views/`: menu do plugin
- `logs/`: diretório para logs
- `locales/`: traduções
- `scripts/`: scripts auxiliares

## Funcionalidades
- Autenticação IMAP OAuth2 com Azure
- Coleta e envio de e-mails
- Registro e exibição de erros detalhados
- Tela administrativa para configuração e logs

## Instalação
1. Copie a pasta do plugin para o diretório de plugins do GLPI
2. Execute o script de instalação do PHPMailer:
   ```
   cd glpioauthimapazure/scripts
   bash install_phpmailer.sh
   ```
   (ou rode `composer require phpmailer/phpmailer` na raiz do plugin)
3. Ative o plugin pelo painel do GLPI
4. Configure as credenciais do Azure na tela administrativa do plugin
5. Configure o Azure para permitir autenticação IMAP/SMTP OAuth2 e obtenha Client ID, Secret, Tenant ID e Redirect URI válidos
6. No Azure, defina o Redirect URI para:
   ```
   https://SEU_GLPI/plugins/glpioauthimapazure/front/oauth_callback.php
   ```
7. Acesse a URL de autorização do Azure para obter o código de autorização e finalize o fluxo OAuth2:
   ```
   https://login.microsoftonline.com/{TENANT_ID}/oauth2/v2.0/authorize?client_id={CLIENT_ID}&response_type=code&redirect_uri=https://SEU_GLPI/plugins/glpioauthimapazure/front/oauth_callback.php&response_mode=query&scope=https://outlook.office365.com/.default offline_access
   ```
8. O refresh_token será salvo em `logs/refresh_token.txt` após o callback. Use esse token no hook de coleta de e-mails.
9. Ajuste o hook de coleta de e-mails (`hook.php`) para usar o e-mail e refresh_token corretos do seu ambiente
10. Agende a execução do cron do GLPI para coletar e-mails automaticamente


## Compatibilidade com GLPI 9.5.5 (Modo Legado)

O plugin pode ser instalado opcionalmente em GLPI 9.5.5, mas com as seguintes limitações e riscos:

- **Interface:** Recursos visuais modernos (tabelas responsivas, ícones, temas escuros) podem não funcionar corretamente.
- **APIs e Integração:** Algumas APIs, métodos de hook e integração podem não estar disponíveis ou apresentar comportamento diferente.
- **Segurança:** Funcionalidades de CSRF, sessões e proteção contra ataques podem ser menos robustas.
- **Plugins/Hooks:** O suporte a plugins e hooks pode ser limitado ou exigir adaptações manuais.
- **Testes:** Testes automatizados e integração contínua não são garantidos para GLPI < 10.
- **Dependências:** Certifique-se de que as extensões PHP (IMAP, cURL, OpenSSL) estejam ativas e atualizadas.

**Recomendação:** Sempre que possível, utilize o GLPI 10.x ou superior para garantir total compatibilidade e segurança. Caso opte por usar em 9.5.5, revise cuidadosamente logs, permissões e funcionalidades após a instalação.


**Adaptações sugeridas para GLPI 9.5.5:**
- Teste todos os fluxos críticos (autenticação, coleta de e-mails, criação de chamados, logs, auditoria).
- Revise a criação de tabelas no `setup.php` para garantir compatibilidade com o MySQL/MariaDB do seu ambiente.
- Ajuste chamadas de métodos/constantes que não existam em versões antigas, como:
  - Use `defined('GLPI_VERSION')` antes de acessar a constante.
  - Para hooks, utilize funções como `plugin_init_glpioauthimapazure`, `plugin_version_glpioauthimapazure`, `plugin_glpiinstall_glpioauthimapazure` e `plugin_glpiuninstall_glpioauthimapazure` no `setup.php`.
  - Se necessário, adapte o menu para HTML puro, evitando classes CSS modernas.
  - Evite dependência de APIs JavaScript modernas ou recursos de interface exclusivos do GLPI 10.
- Para logs e auditoria, utilize apenas métodos básicos de acesso ao banco (`$DB->query`, `$DB->fetch_assoc`) caso métodos utilitários não estejam disponíveis.
- Caso encontre problemas com sessões ou CSRF, implemente verificações manuais usando `$_SESSION` e tokens simples.
- Para problemas de visualização, simplifique tabelas e formulários para HTML básico.
- Consulte a documentação oficial do GLPI 9.5.5 para detalhes de hooks e APIs suportadas.

**Relate problemas de compatibilidade** abrindo uma issue no repositório ou enviando detalhes para o autor.

## Fluxo de uso
1. Configure o Azure e salve as credenciais no plugin
2. Realize o fluxo OAuth2 para obter o refresh_token inicial acessando o endpoint de callback do plugin
3. O plugin usará o refresh_token para renovar o access_token e coletar e-mails via IMAP OAuth2
4. E-mails coletados podem ser convertidos em chamados automaticamente (ajuste o hook conforme sua necessidade)
5. Todos os erros e eventos são registrados e podem ser visualizados na tela de logs do plugin
