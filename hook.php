<?php
function plugin_authhistory_install() {
    // Não precisa criar tabela — usa a tabela nativa glpi_events do GLPI.
    return true;
}

function plugin_authhistory_uninstall() {
    // Não há tabela customizada para remover.
    return true;
}

/**
 * Hook init_session: disparado pelo GLPI após autenticar o usuário com sucesso.
 *
 * 1. Lê o marcador de sessão $_SESSION['authhistory_sso_provider'] (setado pelos
 *    plugins govbrsso / googlesso, se instalados).
 * 2. Localiza o evento de login mais recente em glpi_events.
 * 3. Atualiza o evento com:
 *    - items_id = users_id (para filtrar por usuário na aba)
 *    - Username na message (se ausente, como nos logins SSO)
 *    - Provedor SSO na message (se aplicável)
 */
function plugin_authhistory_init_session() {
    global $DB;

    $users_id = Session::getLoginUserID();
    if (!$users_id) {
        return;
    }

    // Lê e consome o marcador SSO (se existir).
    $sso_provider = $_SESSION['authhistory_sso_provider'] ?? null;
    unset($_SESSION['authhistory_sso_provider']);

    // Determina o sufixo SSO para a message.
    $sso_suffix = '';
    if ($sso_provider === 'govbr') {
        $sso_suffix = ' via Gov.BR (SSO)';
    } elseif ($sso_provider === 'google') {
        $sso_suffix = ' via Google Workspace (SSO)';
    }

    // Busca o evento de login mais recente (último ID inserido com service='login').
    // O GLPI core insere esse evento durante Auth::login() / Session::init(),
    // que acontece ANTES deste hook disparar.
    $iterator = $DB->request([
        'FROM'  => 'glpi_events',
        'WHERE' => [
            'service' => 'login',
        ],
        'ORDER' => 'id DESC',
        'LIMIT' => 1,
    ]);

    if (count($iterator) === 0) {
        return;
    }

    $event = $iterator->current();
    $event_id = $event['id'];
    $message  = $event['message'];

    // Obtém o username do usuário logado.
    $user = new User();
    $username = '';
    if ($user->getFromDB($users_id)) {
        $username = $user->fields['name'];
    }

    // Se a message não contém o username (típico de logins SSO onde o core
    // registra apenas "fez login no IP x.x.x.x"), prepende o username.
    if ($username !== '' && strpos($message, $username) === false) {
        // O core gera mensagens como " fez login no IP ..." ou "fez login no IP ..."
        $message = $username . ' ' . ltrim($message);
    }

    // Adiciona o sufixo SSO se aplicável e se ainda não está presente.
    if ($sso_suffix !== '' && strpos($message, $sso_suffix) === false) {
        $message .= $sso_suffix;
    }

    // Atualiza o evento: items_id para filtrar na aba + message enriquecida.
    $DB->update('glpi_events', [
        'items_id' => $users_id,
        'message'  => $message,
    ], [
        'id' => $event_id,
    ]);
}
