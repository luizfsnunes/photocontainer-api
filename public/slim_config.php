<?php
$slimParams = [
    'settings.responseChunkSize' => 4096,
    'settings.outputBuffering' => 'append',
    'settings.determineRouteBeforeAppMiddleware' => false,
    'settings.displayErrorDetails' => false,
];

if (!is_file(CACHE_DIR.'/routes.cache')) {
    $slimParams = ['settings.routerCacheFile' => CACHE_DIR.'/routes.cache'];
}

if (DEBUG_MODE) {
    $slimParams = [
        'settings.displayErrorDetails' => true,
        'settings.debug' => true,
    ];
}

$slimParams['logger'] = function($c) {
    $logger = new \Monolog\Logger('API_LOG');
    $file_handler = new \Monolog\Handler\StreamHandler("../logs/api.log");
    $logger->pushHandler($file_handler);
    return $logger;
};

$slimParams['errorHandler'] = function ($c) {
    return function (\Psr\Http\Message\ServerRequestInterface $request, $response, Exception $e) use ($c) {
        $trace = $e->getTrace();

        $data = [
            'file' => $e->getFile() . ': '. $e->getLine(),
            'route' => $request->getMethod(). ' ' . $request->getUri()->getPath(),
            'actionClass' => $trace[0],
            'contextClass' => $trace[1],
        ];

        $body = $request->getParsedBody();
        if (!empty($body)) {
            $data['payload'] = $body;
        }

        $message = get_class($e) == \PhotoContainer\PhotoContainer\Infrastructure\Exception\PersistenceException::class ? $e->getInfraLayerError() : $e->getMessage();

        $c->get('logger')->addCritical($message, $data);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Access-Control-Allow-Origin, X-Requested-With, Content-Type, Accept, Origin, Authorization, Cache-Control, Expires')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
            ->withHeader('Access-Control-Max-Age', '604800')
            ->withJson(['message' => $e->getMessage()], 500);
    };
};

return $slimParams;