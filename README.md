# SilverStripe Cache Assist


[![Latest Stable Version](https://poser.pugx.org/nyeholt/silverstripe-cache-assist/version.svg)](https://github.com/nyeholt/silverstripe-cache-assist/releases)
[![Latest Unstable Version](https://poser.pugx.org/nyeholt/silverstripe-cache-assist/v/unstable.svg)](https://packagist.org/packages/nyeholt/silverstripe-cache-assist)
[![Total Downloads](https://poser.pugx.org/nyeholt/silverstripe-cache-assist/downloads.svg)](https://packagist.org/packages/nyeholt/silverstripe-cache-assist)
[![License](https://poser.pugx.org/nyeholt/silverstripe-cache-assist/license.svg)](https://github.com/nyeholt/silverstripe-cache-assist/blob/master/LICENSE.md)


Helpers for working with upstream caches, in particular cloudflare

## Composer Install

```
composer require nyeholt/silverstripe-cache-assist:~1.0
```

## Requirements

* SilverStripe 4.1+

## Documentation

To activate, add the following options to your project config


```
# Add to any custom controllers also 
# to prevent caching
---
Name: cache_extensions
---
PageController:
  extensions:
    - Symbiote\Cache\CacheHeaderExtension

---
Name: project_middleware
After:
  - requestprocessors
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\Control\Director:
    properties:
      Middlewares:
        CacheCookieMiddleware: %$Symbiote\Cache\CacheCookieMiddleware

```
