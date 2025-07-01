<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/db.php';

use FastRoute\RouteCollector;

header('Content-Type: application/json; charset=UTF-8');

// deve passar a classe completa,exemplo:
// $r->get('/teste', 'App\Controllers\teste@listar');
function resolveHandler($handler) { 
    if (is_string($handler) && strpos($handler, '@') !== false) {
        list($class, $method) = explode('@', $handler);
        if (class_exists($class)) {
            $obj = new $class();
            return [$obj, $method];
        }
        throw new Exception("Classe $class não encontrada.");
    }
    return $handler;
}

function multiHandler($handlers, $vars = []) {
    foreach ($handlers as $handler) {
        $callable = resolveHandler($handler);
        $result = call_user_func_array($callable, $vars);
        // Se algum middleware der exit ou retornar false, para aqui
        if ($result === false) {
            break;
        }
    }
}

$dispatcher = FastRoute\simpleDispatcher(function (RouteCollector $r) {
require_once __DIR__ . '/routes/routes.php'; // Carrega as rotas
});

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];


if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
// Remove query string da URI, se existir
// Exemplo: /api/participantes?search=álvaro -> /api/participantes
// strpos verifica a posição do primeiro caractere de '?'
// substr extrai a parte da string antes do '?'
// Se não houver '?', retorna a URI original

$uri = rawurldecode($uri);
// Decodifica a URI para lidar com caracteres especiais
// Exemplo: /api/participantes/%C3%A1lvaro -> /api/participantes/álvaro
// rawurldecode converte %C3%A1 para á

$basePath = '/api';//remove subpasta base da api
if (str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

$routeInfo = $dispatcher->dispatch($method, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(['erro' => 'Rota não encontrada']);
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido']);
        break;

    case FastRoute\Dispatcher::FOUND:
        try {
            $handlers = $routeInfo[1];
            if (is_array($handlers)) {//encadear vários handers
                multiHandler($handlers, $routeInfo[2]);
            } else {
                $handler = resolveHandler($handlers);
                call_user_func_array($handler, $routeInfo[2]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['erro' => 'Falha ao processar a requisição', 'detalhe' => $e->getMessage()]);
        }
        break;
}
