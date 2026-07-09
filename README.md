# GLPI Auth History Plugin

Um plugin leve e eficiente para o GLPI que adiciona rastreabilidade completa e detalhada sobre os acessos dos usuários ao sistema. Diferente dos logs padrões do sistema, este plugin identifica com precisão a "porta de entrada" (método de autenticação) utilizada no momento do login, mesmo quando o usuário utiliza múltiplos métodos de Single Sign-On (SSO).

## 🚀 Funcionalidades

*   **Rastreamento Preciso de Origem:** Identifica a verdadeira origem da sessão atual, contornando a limitação do GLPI que apenas exibe o método padrão de criação do usuário.
*   **Detecção de SSO Customizado:** Suporte nativo para detectar logins advindos dos plugins Gov.BR SSO e Google Workspace SSO.
*   **Histórico de Acesso por Usuário:** Adiciona uma nova aba ("Histórico de Acesso") diretamente no formulário de perfil de cada usuário (`Administração > Usuários`).
*   **Segurança e Privacidade:** A visualização da aba de histórico é estritamente restrita a administradores utilizando o perfil **Super-Admin**. Outros perfis, mesmo com permissão de leitura em usuários, não terão acesso a estes dados.
*   **Integração Visual Nativa:** Utiliza o motor de abas padrão do GLPI 10/11, renderizando badges e ícones perfeitamente alinhados com a nova interface.

## 📋 Pré-requisitos

*   **GLPI:** Compatível com as versões `10.0.x` até `11.1.x`.
*   **PHP:** Mesmos requisitos do GLPI (recomendado 8.1+).

## ⚙️ Instalação

1.  Baixe ou clone este repositório.
2.  Renomeie a pasta principal do plugin para `authhistory` (caso não esteja com este nome).
3.  Mova a pasta `authhistory` para dentro do diretório `plugins/` do seu servidor GLPI.
    *   Exemplo de caminho final: `/var/www/html/glpi/plugins/authhistory/`
4.  Acesse o GLPI com perfil de Super-Admin.
5.  Navegue até **Configurar > Plugins**.
6.  Encontre o "Histórico de Autenticação" na lista, clique em **Instalar** e logo após em **Ativar**.

## 💻 Como utilizar

O plugin opera de forma invisível durante o processo de login (`hook: init_session`).
Para visualizar os dados capturados:

1.  Acesse o painel com seu perfil **Super-Admin**.
2.  Vá até **Administração > Usuários**.
3.  Abra o cadastro de qualquer usuário.
4.  No menu lateral (ou superior), clique na aba **Histórico de Acesso** (<i class="fas fa-history"></i>).
5.  A tabela listará os últimos 100 acessos daquele usuário, informando a Data/Hora, o IP de origem e o Método (Ex: Banco Local, LDAP, Google Workspace, Gov.BR).

## 🛠️ Personalizando novos SSOs

Se a sua instituição utiliza outros métodos de SSO, você pode estender o rastreamento editando o arquivo `hook.php`. Basta adicionar uma nova condição olhando para a URL de callback (variável `REQUEST_URI`) ou para as variáveis customizadas do seu plugin de SSO, atribuindo um ID customizado e depois mapeando o nome amigável no arquivo `inc/log.class.php`.