<?php

namespace Symbiote\Cache;

use SilverStripe\Security\Member;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Control\HTTP;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Controller;


/**
 * Added to controllers that want to ensure their items are cached
 */
class CacheHeaderExtension extends Extension
{
    private static $global_cache_age = 600;

    public function onAfterInit()
    {
        if (!$this->owner instanceof Controller) {
            return;
        }

        if (!$this->owner->getRequest()) {
            return;
        }
        if (count($this->owner->getRequest()->requestVars()) === 0
            && !Member::currentUserID()
            && Versioned::get_stage() === Versioned::LIVE) {
                $pageAge = $this->owner->config()->cache_age;
                $global = Config::inst()->get(CacheHeaderExtension::class, 'global_cache_age');
                if ($pageAge === null) {
                    $pageAge = $global;
                }

                HTTP::set_cache_age($pageAge);

                // NOTE(Marcus) 2012-12-11
                //
                // we _NEED_ to set a modification date here to ensure the upstream
                // cache knows to pickup changes to our dynamic token
                // We set this to the cahce age we're after minus a couple of seconds so that
                // the upstream cache doesn't see the modified time as later than when it last made
                // a request here.
                HTTP::register_modification_timestamp(time() - $pageAge - 2);
        }
    }
}
