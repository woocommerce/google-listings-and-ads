#!/bin/bash

echo Removing googleads lib versions 4,5
rm -rf vendor/googleads/google-ads-php/metadata/Google/Ads/GoogleAds/V{4,5}
rm -rf vendor/googleads/google-ads-php/src/Google/Ads/GoogleAds/V{4,5}
rm -rf vendor/googleads/google-ads-php/src/Google/Ads/GoogleAds/Util/V{4,5}
rm -rf vendor/googleads/google-ads-php/src/Google/Ads/GoogleAds/Lib/V{4,5}
