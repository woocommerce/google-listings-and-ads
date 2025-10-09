# Google tag gateway for advertisers

## About the library

This is a PHP library that allows PHP developers to set-up Google tag gateway
for advertisers on a website that uses PHP (e.g. WordPress, Drupal, etc.).
Google tag gateway for advertisers lets you deploy a Google tag hosted on your
website's domain. This library sits between your website and Google's services
and redirects both script and measurement requests made to your first-party
domain to Google.

Reference:
[https://developers.google.com/tag-platform/tag-manager/gateway/setup-guide?setup=manual](https://developers.google.com/tag-platform/tag-manager/gateway/setup-guide?setup=manual)

## Installation

Install using composer:

```shell
composer require google/gtg-ads
```

## Usage

The base library is platform agnostic and can be used in any PHP environment.

The library also has the following platform specific Adapters which will do most
of the heavy lifting of enabling Google Tag Gateway on the page by routing
traffic to a randomly generated, or a provided, first-party path on the website
as well as inject the Google tag snippet on your page for a specific Tag ID.

Platform specific adapters available:

*   [Wordpress](#wordpress-usage)

### Wordpress Usage {#wordpress-usage}

The library exposes an `Adapter` class under the namespace
`Google\GoogleTagGatewayLibrary\Wordpress\Adapter`.

This adapter has 3 methods:

#### ::create()

Creates the base class to use within the server.

##### Example

```php
use Google\GoogleTagGatewayLibrary\Wordpress\Adapter

$gtgAdapter = Adapter::create();
```

#### \-\>initialize()

This function should be called on every request to the server that should
generate the Google Tag JS snippet on page, as well as add the rewrite rules for
the first-party measurement path on the site.

###### Example

```php
// Root of every page load.
$gtgAdapter->initialize();
```

#### \-\>update(array $values)

This function should be called at least once in order for the relevant values
used in the initialize function to be propagated properly. Ideally this is
called during a user interaction or on the very first load of a plugin.

You should be cautious of overly calling this function as it makes an effort to
call `flush_rewrite_rules`, if values have changed, which can be costly to
performance if called excessively.

The function accepts two optional values:

*   `tagId` \- The primary tag ID that will be loaded on page.
*   `measurementPath` \- A custom path to route measurement requests to. This
    will route the given path to a measurement.php proxy script file. If left
    blank and none is set when calling this method a random alpha-numeric path
    will be set for you.

##### Example

```php
// Some user interaction happens or this is the first load of a plugin.
$gtgAdapter->update([
    'tagId' => 'G-12345',
    // Random measurement path will be generated if one hasn't been set prior
]);
```

### Generic Usage

This should only be used if your PHP environment doesn’t already have an Adapter
class above as it requires more manual setup.

The core functionality will attempt to redirect measurement scripts and beacon
requests. The requests will be routed to a `measurement.php` file which lives on
the server and then it will forward the traffic to the relevant Google services.
The helper library can generate a Google tag snippet which will be served
first-party and can be injected directly into your site.

When installing the library on a server a measurement.php script file will be
loaded with the package. On installation in order to ensure durable measurement
the following step ***must*** be taken.

#### Re-route requests to measurement.php

Create a URL rule for your PHP website that routes request to a specific path
(for example: `/my/measurement`) and re-route those requests to measurement.php
in the following format:
`/path/on/server/to/resources/gtg-library/src/measurement.php?id={TAG_ID}&mpath={MEASUREMENT_PATH}&s={EVERYTHING_AFTER_MEASUREMENT_PATH}`

#### Use GoogleTagGatewayHelper

The library contains a generic helper class for generating the correct script
tags to serve onto the page.

##### new GoogleTagGatewayHelper(string $tagId, array $options)

The constructor of the class allows you to pass in the tag ID and an options
argument with additional specifications.

The tag ID passed in here should be the same TAG\_ID you configured above in the
URL rule.

The following options can be provided:

*   `mpath` \- The MEASUREMENT\_PATH that was configured above which will
    re-route requests to measurement.php.

###### *Example*

```php
use Google\GoogleTagGatewayLibrary\GoogleTagGatewayHelper;

$gtgHelper = new GoogleTagGatewayHelper(
  'TAG_ID',
  ['mpath' => 'MEASUREMENT_PATH'],
);
```

##### \-\>createResources()

This will generate the script resources that should be injected onto the page as
high up in `<head>` tag as possible.

This function has the potential to throw `Exception` errors if you have an
invalid TAG\_ID or MEASUREMENT\_PATH.

The return value will contain an associative array containing the following:

*   `src` \- A src attribute to be included on one script tag. This tag should
    also include the async attribute with it. This will load the tag.
*   `script` \- The main body of a script tag. Configures your tag.
*   `topScript` \- The main body of another script tag included on the page
    before any of the other scripts. This will ensure that your tag is load
    first-party.

###### *Example*

```php
$resources = $gtgHelper->createResources();

// In the following order inject 3 different script tags onto the page as high
// up inside of the <head> tag:

// Add a script tag to the very top of the page using $resource['topScript']
// as the script's contents

// Add an async script tag to the page using $resources['src'] as the script's
// src attribute

// Add a script tag to the page using $resource['script'] as the script's
// contents

```

##### \-\>healthCheck()

This function can be used to check if the measurement.php script as well as your
custom URL rules are working as intended.

The return value will contain an associative array containing the following
values:

*   `status` \- A boolean value which indicates whether everything is working as
    intended.
*   `errorMessage` \- Will be populated if `status` is `false` with the best
    reason as to what is not working correctly.

###### *Example*

```
$gtgHealthy = $gtgHelper->healthCheck();

if (!$gtgHealthy['status']) {
  // Log a warning with the contents of $gtgHealthy['errorMessage']
}
```
