<?php

namespace Symbiote\Cache;

use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Control\Cookie;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;


class CacheCookieMiddleware implements HTTPMiddleware
{
    const NO_CACHE_HEADER = 'X-NoCache';
    const SKIP_CACHE_COOKIE_HEADER = 'X-SkipNoCache';

    private static $cookie_name = 'cf_nocache';

    /**
     * Used for test purposes only
     */
    public $forceCookie = false;

    public function process(HTTPRequest $request, callable $delegate)
    {
        $response = $delegate($request);
        if (!($response instanceof HTTPResponse)) {
            return $response;
        }

        // skip a cache bypass if we're told to, ie we WANT to cache
        if ($response->getHeader(self::SKIP_CACHE_COOKIE_HEADER)) {
            return $response;
        }

        $cookie = false;

        // we output a bypass if logged in
        if (Member::currentUserID() > 0 ||
            Versioned::get_stage() != Versioned::LIVE) {
            $cookie = true;
        } else if ($response->getStatusCode() !== 200) {
            $cookie = true;
        } else if ($response->getHeader(self::NO_CACHE_HEADER)) {
            $cookie = true;
        } else {
            $body = $response->getBody();
            if (strpos($body, SecurityToken::inst()->getValue()) !== false) {
                $cookie = true;
            }
        }

        if ($cookie && (!headers_sent() || $this->forceCookie)) {
            Cookie::set(Config::inst()->get(self::class, 'cookie_name'), '1');
        }

        return $response;
    }
}
