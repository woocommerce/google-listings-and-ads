/**
 * Internal dependencies
 */
import { APPEARANCE } from '~/components/account-card';
import wpLogoURL from '~/images/logo/wp-logo.svg';
import googleLogoURL from '~/images/logo/google-g-logo.svg';
import googleMCLogoURL from '~/images/logo/google-merchant-center-logo.svg';
import googleAdsLogoURL from '~/images/logo/google-ads-logo.svg';
import youTubeLogoURL from '~/images/logo/youtube-logo.svg';

/**
 * Maps an account card appearance to its logo image URL.
 */
export const ACCOUNT_LOGOS = {
	[ APPEARANCE.WPCOM ]: wpLogoURL,
	[ APPEARANCE.GOOGLE ]: googleLogoURL,
	[ APPEARANCE.GOOGLE_MERCHANT_CENTER ]: googleMCLogoURL,
	[ APPEARANCE.GOOGLE_ADS ]: googleAdsLogoURL,
	[ APPEARANCE.YOUTUBE ]: youTubeLogoURL,
};
