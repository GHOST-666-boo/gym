<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ImageAccessControlMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ImageAccessControlMiddlewareTest extends TestCase
{
    protected ImageAccessControlMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ImageAccessControlMiddleware();
    }

    public function test_allows_request_within_rate_limit()
    {
        Config::set('image_protection.rate_limit.max_requests', 10);
        Config::set('image_protection.rate_limit.time_window', 60);
        
        $request = Request::create('/protected/image/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $request->headers->set('Referer', 'http://localhost');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_blocks_request_exceeding_rate_limit()
    {
        Config::set('image_protection.rate_limit.max_requests', 2);
        Config::set('image_protection.rate_limit.time_window', 60);
        
        $request = Request::create('/protected/image/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.2');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $request->headers->set('Referer', 'http://localhost');

        // Make requests up to the limit
        for ($i = 0; $i < 2; $i++) {
            $response = $this->middleware->handle($request, function ($req) {
                return response('OK');
            });
            $this->assertEquals(200, $response->getStatusCode());
        }

        // This request should be blocked
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals('Too Many Requests', $response->getContent());
    }

    public function test_blocks_suspicious_user_agents()
    {
        $suspiciousAgents = ['wget', 'curl', 'python-requests', 'scrapy', 'bot'];
        
        foreach ($suspiciousAgents as $agent) {
            $request = Request::create('/protected/image/test', 'GET');
            $request->server->set('REMOTE_ADDR', '192.168.1.3');
            $request->headers->set('User-Agent', $agent);
            $request->headers->set('Referer', 'http://localhost');

            $response = $this->middleware->handle($request, function ($req) {
                return response('OK');
            });

            $this->assertEquals(403, $response->getStatusCode());
            $this->assertEquals('Forbidden', $response->getContent());
        }
    }

    public function test_blocks_empty_user_agent()
    {
        $request = Request::create('/protected/image/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.4');
        $request->headers->set('Referer', 'http://localhost');
        // Explicitly set empty User-Agent
        $request->headers->set('User-Agent', '');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('Forbidden', $response->getContent());
    }

    public function test_allows_valid_referrer()
    {
        $request = Request::create('/protected/image/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.5');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $request->headers->set('Referer', 'http://localhost/products/test');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_allows_no_referrer()
    {
        $request = Request::create('/protected/image/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.6');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        // No Referer header

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}