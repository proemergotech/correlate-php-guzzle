<?php

/**
 * @author Soma Szelpal <szelpalsoma@gmail.com>
 * @license MIT
 */

namespace ProEmergotech\Correlate\Guzzle;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ProEmergotech\Correlate;

class GuzzleCorrelateMiddleware
{
    /**
     * @var string
     */
    protected $correlationId = '';

    /**
     * @param string $correlationId
     */
    public function __construct($correlationId = null)
    {
        if ($correlationId !== null) {
            $this->correlationId = (string) $correlationId;
        }
    }

    /**
     * @param  array  $record
     * @return array
     */
    public function __invoke(callable $handler)
    {
        $cid = $this->correlationId();

        return function (RequestInterface $request, array $options) use ($handler, $cid) {

            if (!isset($options['headers'][Correlate::getHeaderName()]) && empty($cid)) {

                $check = [
                    'json',
                    'form_params',
                    'query'
                ];

                foreach ($check as $c) {
                    if (!isset($options[$c][Correlate::getParamName()])) {
                        continue;
                    }
                    $cid = $options[$c][Correlate::getParamName()];
                    break;
                }
            }

            return $handler(function (RequestInterface $request) use ($cid) {
                if (!$request->hasHeader(Correlate::getHeaderName())) {
                    return $request->withHeader(
                        Correlate::getHeaderName(), (string)$cid
                    );
                }
                return $request;
            }, $options)->then(function (ResponseInterface $response) use ($cid) {
                /*
                if (!$response->hasHeader(Correlate::getHeaderName())) {
                    return $response->withHeader(
                        Correlate::getHeaderName(), $cid
                    );
                }
                */
                return $response;
            });
        };
    }
}
