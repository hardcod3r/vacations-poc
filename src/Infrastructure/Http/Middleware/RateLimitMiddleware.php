<?php

declare(strict_types=1);

namespace Infrastructure\Http\Middleware;

use Predis\Client as RedisClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RateLimitMiddleware
{
    public function __construct(
        private RedisClient $redis,
        private int $limit = 5,   // requests
        private int $window = 60  // seconds
    ) {}

    /**
     * @param  callable(ServerRequestInterface):ResponseInterface  $next
     */
    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $key = $this->key($request);
        $count = (int) $this->redis->incr($key);

        if ($count === 1) {
            $this->redis->expire($key, $this->window);
        }

        if ($count > $this->limit) {
            $ttl = (int) $this->redis->ttl($key);
            $msg = $ttl > 0 ? "Too many requests. Retry after {$ttl}s." : 'Too many requests.';
            throw new \RuntimeException($msg);
        }

        $response = $next($request);

        $remaining = max(0, $this->limit - $count);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->limit)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining);
    }

    private function key(ServerRequestInterface $request): string
    {
        $srv = $request->getServerParams();

        $ipRaw = $srv['REMOTE_ADDR'] ?? null;
        $ip = \is_string($ipRaw) && $ipRaw !== '' ? $ipRaw : 'unknown';

        $xff = $request->getHeaderLine('X-Forwarded-For');
        if ($xff !== '') {
            $first = \trim(\explode(',', $xff)[0]);
            if ($first !== '') {
                $ip = $first;
            }
        }

        $path = $request->getUri()->getPath(); // string by interface

        return 'ratelimit:'.$ip.':'.$path;
    }
}
