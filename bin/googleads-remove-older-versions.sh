#!/bin/bash

echo Removing googleads lib versions 3,4,5
rm -rf vendor/googleads/google-ads-php/metadata/Google/Ads/GoogleAds/V{3,4,5}
rm -rf vendor/googleads/google-ads-php/src/Google/Ads/GoogleAds/V{3,4,5}
rm -rf vendor/googleads/google-ads-php/src/Google/Ads/GoogleAds/Util/V{3,4,5}
rm -rf vendor/googleads/google-ads-php/src/Google/Ads/GoogleAds/Lib/V{3,4,5}
