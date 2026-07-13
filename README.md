# GLPI Auth History Plugin

Um plugin leve e eficiente para o GLPI que adiciona uma aba de histórico detalhado sobre os acessos dos usuários ao sistema. Diferente da exibição padrão, que mostra apenas a última conexão, este plugin lista o histórico completo de logins extraído diretamente da tabela nativa de eventos do GLPI (`glpi_events`).

## 🚀 Funcionalidades

*   **Histórico de Acesso por Usuário:** Adiciona uma nova aba ("Histórico de Acesso") diretamente no formulário de perfil de cada usuário (`Administração > Usuários`).
*   **Totalmente Desacoplado:** O plugin atua **apenas como interface gráfica**. Ele não cria tabelas customizadas nem intercepta o processo de login, tornando-o extremamente leve e seguro de usar ou desinstalar sem perder dados.
*   **Histórico Retroativo:** Como lê a tabela nativa `glpi_events`, o plugin é capaz de mostrar os logins que ocorreram mesmo antes da sua instalação.
*   **Detecção de SSO Customizado:** Lê nativamente os registros gerados pelos plugins **[Gov.BR SSO](https://github.com/andrefelipeufcg/govbrsso)** e **[Google SSO](https://github.com/andrefelipeufcg/googlesso)**, que gravam de forma autônoma a identificação do provedor no histórico.
*   **Segurança e Privacidade:** A visualização da aba de histórico é estritamente restrita a administradores utilizando o perfil **Super-Admin**. Outros perfis não terão acesso a estes dados.
*   **Integração Visual Nativa:** Utiliza o motor de abas padrão do GLPI 10/11, renderizando perfeitamente alinhado com a interface do sistema.

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

Como o plugin opera apenas como uma camada de visualização, não há configurações extras.
Para visualizar os dados:

1.  Acesse o painel com seu perfil **Super-Admin**.
2.  Vá até **Administração > Usuários**.
3.  Abra o cadastro de qualquer usuário.
4.  No menu lateral (ou superior), clique na aba **Histórico de Acesso**.
5.  A tabela listará os últimos 100 acessos daquele usuário, informando a Data/Hora, o IP de origem e o Método (Ex: Banco de Dados Local, Google Workspace (SSO), Gov.BR (SSO)).

## 🛠️ Personalizando novos SSOs (Arquitetura)

Se você desenvolve outros plugins de SSO para o GLPI e deseja que eles apareçam corretamente no Histórico de Acesso, **não é necessário alterar o `authhistory`**.

Basta que o seu plugin de SSO registre o evento de login na tabela nativa `glpi_events` (via método `Event::log()`), informando o ID do usuário (`items_id`) e colocando o nome do provedor no texto da mensagem (`message`). 

**Exemplo de formato esperado na mensagem:**
> `usuario.exemplo fez login no IP 127.0.0.1 via Meu SSO Customizado`

Tudo o que estiver escrito após a palavra **"via"** será automaticamente extraído e exibido pelo `authhistory` na coluna de Método de Autenticação!