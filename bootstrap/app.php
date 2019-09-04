<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
  dirname(__DIR__)
))->bootstrap();

$app = new Laravel\Lumen\Application(
  dirname(__DIR__)
);

$app->configure('database');

$app->withFacades();

$app->singleton(
  Illuminate\Contracts\Debug\ExceptionHandler::class,
  App\Exceptions\Handler::class
);

$app->router->group([
  'namespace' => 'App\Http\Controllers',
], function ($router) {
  require __DIR__.'/../routes/web.php';
});

return $app;
