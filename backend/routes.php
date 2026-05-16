<?php

session_start();

$method = $_SERVER['REQUEST_METHOD'];

$request = '';
if (!empty($_SERVER['PATH_INFO'])) {
    $request = $_SERVER['PATH_INFO'];
} else {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $request = preg_replace('#^' . preg_quote($scriptName, '#') . '#', '', $requestUri);
}

$request = '/' . trim($request, '/');
if ($request === '/') {
    $request = '/';
}

if (!empty($_GET['route'])) {
    $request = '/' . trim($_GET['route'], '/');
}

$routes = [
    '/login'      => ['file' => __DIR__ . '/auth.php', 'methods' => ['POST']],
    '/categories'=> ['file' => __DIR__ . '/categoryAuth.php', 'methods' => ['POST']],
    '/products'  => ['file' => __DIR__ . '/productAuth.php', 'methods' => ['POST']],
    '/users'     => ['file' => __DIR__ . '/userAuth.php', 'methods' => ['POST']],
    '/sales'     => ['file' => __DIR__ . '/saleAuth.php', 'methods' => ['GET', 'POST']],
    '/reports'   => ['file' => __DIR__ . '/reportAuth.php', 'methods' => ['GET', 'POST']],
];

$matched = false;

foreach ($routes as $routePattern => $routeData) {
    if ($request === $routePattern) {
        $matched = true;

        if (!in_array($method, $routeData['methods'])) {
            http_response_code(405);
            echo "405 - Method Not Allowed";
            exit;
        }

        require $routeData['file'];
        break;
    }
}

if (!$matched) {
    http_response_code(404);
    echo "404 - Page Not Found";
}
