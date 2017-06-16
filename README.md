# correlate-php-guzzle

---

## Overview

It's very difficult to track a request accross the system when we are working with microservices. We came out a solution for that. We generate a unique version 4 uuid for every request and every service passes this id via request header to other services. We call this **correlation ID**.

## Packages

- [proemergotech/correlate-php-laravel](https://github.com/proemergotech/correlate-php-laravel)
  - Middleware for Laravel and Lumen frameworks.
- [proemergotech/correlate-php-psr-7](https://github.com/proemergotech/correlate-php-psr-7)
  - Middleware for any PSR-7 compatible frameworks like [Slim Framework](https://www.slimframework.com/).
- [proemergotech/correlate-php-monolog](https://github.com/proemergotech/correlate-php-monolog)
  - Monolog processor for correlate middlewares (you don't have to use this directly).
- [proemergotech/correlate-php-guzzle](https://github.com/proemergotech/correlate-php-guzzle)
  - Guzzle middleware to add correlation id to every requests.
- [proemergotech/correlate-php-core](https://github.com/proemergotech/correlate-php-core)
  - Common package for correlate id middlewares to provide consistent header naming accross projects.

## Installation

- Install via composer

```sh
composer require proemergotech/correlate-php-guzzle
```

## Setup for Slim Framework

**This example assumes you are already using [proemergotech/correlate-php-psr-7](https://github.com/proemergotech/correlate-php-psr-7) middleware!**

```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use ProEmergotech\Correlate\Correlate;
use ProEmergotech\Correlate\Guzzle\GuzzleCorrelateMiddleware;
use Monolog\Logger;

$app = new \Slim\App();

$container = $app->getContainer();

$container['logger'] = function($container) {
  return new Logger();
};

$container['httpClient'] = function ($container) {
  $cid = $container['request']->getAttribute(
    Correlate::getParamName()
  );
  $stack = HandlerStack::create(new CurlHandler());
  $stack->push(new GuzzleCorrelateMiddleware($cid));
  return new Client(['handler' => $stack]);
};

// See "proemergotech/correlate-php-psr-7" project
$app->add(new \ProEmergotech\Correlate\Psr7\Psr7CorrelateMiddleware($container['logger']));

/**
 * Example GET route
 *
 * @param  \Psr\Http\Message\ServerRequestInterface $req  PSR7 request
 * @param  \Psr\Http\Message\ResponseInterface      $res  PSR7 response
 * @param  array                                    $args Route parameters
 *
 * @return \Psr\Http\Message\ResponseInterface
 */
$app->get('/foo', function ($req, $res, $args) {

    $httpClient = $this->get('httpClient');
    $httpClient->request('GET', 'http://httpbin.org/');

    // You can override correlation id here
    $httpClient->request('GET', 'http://httpbin.org/', [
      'headers' => [
        Correlate::getHeaderName() => Correlate::id()
      ],
    ]);

    return $res;
});
```

## Setup for Laravel 5

Write a service provider to register a guzzle instance for future usage. Create a handler stack and push the middleware to it.

**This example assumes you are already using [proemergotech/correlate-php-laravel](https://github.com/proemergotech/correlate-php-laravel) middleware!**

```php
<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ProEmergotech\Correlate\Guzzle\GuzzleCorrelateMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class GuzzleHttpClientProvider extends ServiceProvider
{
  public function register()
  {
    $this->app->bind('guzzle', function () {

      // Determine correlation id.
      $cid = $this->app['request']->getCorrelationId(); // If you use proemergotech/correlate-php-laravel middleware

      $stack = HandlerStack::create(new CurlHandler());
      $stack->push(new GuzzleCorrelateMiddleware($cid));

      $config = isset($this->app['config']['guzzle']) ? $this->app['config']['guzzle'] : [];

      $config['handler'] = $stack;

      return new Client($config);
    });
  }
}

```

Add serverice provider to config/app.php in your Laravel project.

```php
// config/app.php

    'providers' => [
        ...

        \App\Providers\GuzzleHttpClientProvider::class,
    ],
```

## Setup for Lumen 5

Write a service provider to register a guzzle instance for future usage. Create a handler stack and push the middleware to it.

**This example assumes you are already using [proemergotech/correlate-php-laravel](https://github.com/proemergotech/correlate-php-laravel) middleware!**

```php
// bootstrap/app.php

// ...
$app->bind('guzzle', function () use ($app) {

    // Determine correlation id.
    $cid = $app['request']->getCorrelationId(); // If you use proemergotech/correlate-php-laravel middleware

    $stack = HandlerStack::create(new CurlHandler());
    $stack->push(new GuzzleCorrelateMiddleware($cid));

    return new Client([
      'handler' => $stack
    ]);
  });
// ...

```

## Contributing

See `CONTRIBUTING.md` file.

## Credits

This package developed by [Soma Szélpál](https://github.com/shakahl/) at [Pro Emergotech Ltd.](https://github.com/proemergotech/).

## License

This project is released under the [MIT License](http://www.opensource.org/licenses/MIT).
