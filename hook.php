<?php
function plugin_authhistory_install() {
    // Não precisa criar tabela — usa a tabela nativa glpi_events do GLPI.
    return true;
}

function plugin_authhistory_uninstall() {
    // Não há tabela customizada para remover.
    return true;
}

// O authhistory agora funciona APENAS como interface gráfica (aba de histórico).
// Os próprios plugins SSO (govbrsso, googlesso) enriquecem o glpi_events no momento do login.
// O login local é gravado nativamente pelo GLPI.

