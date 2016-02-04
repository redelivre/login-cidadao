<?php

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
//umask(0000);

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';
Debug::enable();

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();

try {
    $path = implode(DIRECTORY_SEPARATOR,
        array($kernel->getRootDir(), 'config', 'parameters.yml'));

    $params = Yaml::parse(file_get_contents($path));
    Request::setTrustedProxies($params['parameters']['trusted_proxies']);
} catch (Exception $ex) {
    http_response_code(500);
    exit('Invalid configuration');
}

$request = Request::createFromGlobals();

$allowed = $params['parameters']['dev_allowed'];
if (!IpUtils::checkIp($request->getClientIp(), $allowed)) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file.');
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
