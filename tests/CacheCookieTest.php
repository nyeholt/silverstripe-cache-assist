<?php
namespace Symbiote\Cache\Test;

use SilverStripe\Dev\SapphireTest;
use Symbiote\Cache\CacheCookieMiddleware;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\CookieJar;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\SecurityToken;



class CacheCookieTest extends SapphireTest
{
    /**
     * Written as a unit test as mocking the framework Cookie handling
     * is difficult as the injector/config stack is reset on request in a functional test, which
     * means swapping out CookieBackend doesn't work because there's no point at which
     * it can be mocked during the request cycle before middleware kicks in.
     */
    public function testCacheCookie()
    {
        $middleware = new CacheCookieMiddleware();
        $middleware->forceCookie = true;

        $request = new HTTPRequest('GET', '/');

        $jar = new TestCookieJar();
        Injector::inst()->registerService($jar, 'SilverStripe\\Control\\Cookie_Backend');

        Versioned::set_stage('Live');

        $response = $middleware->process($request, function ($request) {
            return new HTTPResponse('<h1>Title</h1>', 200);
        });

        $this->assertEquals(0, count($jar->cookies));

        // okay, change our status to non 200, expect a cookie
        $response = $middleware->process($request, function ($request) {
            return new HTTPResponse('<h1>Title</h1>', 400);
        });

        $this->assertTrue(isset($jar->cookies['cf_nocache']));

        $jar->cookies = [];

        // test Versioned::stage
        Versioned::set_stage('Stage');
        $response = $middleware->process($request, function ($request) {
            return new HTTPResponse('<h1>Title</h1>', 200);
        });
        $this->assertTrue(isset($jar->cookies['cf_nocache']));

        $jar->cookies = [];
        Versioned::set_stage('Live');

        // with the nocache header set
        $response = $middleware->process($request, function ($request) {
            $response = new HTTPResponse('<h1>Title</h1>', 200);
            $response->addHeader(CacheCookieMiddleware::NO_CACHE_HEADER, 1);
            return $response;
        });

        $this->assertTrue(isset($jar->cookies['cf_nocache']));

        $jar->cookies = [];

        // now check for security token
        $response = $middleware->process($request, function ($request) {
            $body = 'some text with security id = ' . SecurityToken::inst()->getValue() . ' set';
            $response = new HTTPResponse($body, 200);
            return $response;
        });

        $this->assertTrue(isset($jar->cookies['cf_nocache']));

        $jar->cookies = [];

        // confirm that the original test still works

        $response = $middleware->process($request, function ($request) {
            return new HTTPResponse('<h1>Title</h1>', 200);
        });

        $this->assertEquals(0, count($jar->cookies));
    }
}

class TestCookieJar extends CookieJar
{
    public $cookies = [];

    public function set($name, $value, $expiry = 90, $path = null, $domain = null, $secure = false, $httpOnly = true)
    {
        $this->cookies[$name] = [
            'value' => $value,
            'expiry' => $expiry,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly
        ];
    }
}
