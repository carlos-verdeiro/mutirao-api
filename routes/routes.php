<?php

$r->addRoute('GET', '/ping', function () {// teste de ping
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(200);
    echo json_encode(['mensagem' => 'pong']);
});

$r->get('/teste', 'App\Controllers\teste@mostrar');
