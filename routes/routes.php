<?php
// Teste de ping
$r->addRoute('GET', '/ping', function () {
    http_response_code(200);
    echo json_encode(['mensagem' => 'pong']);
});

// CONTRIBUIDORES
    //Público
$r->post('/login', 'App\Controllers\Contribuidor@login');
