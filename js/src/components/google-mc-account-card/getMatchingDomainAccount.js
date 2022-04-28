/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

const getMatchingDomainAccount = ( existingAccounts = [] ) => {
	/**
	 * `homeUrl` has a trailing slash,
	 * while `existingAccounts`'s `domain` has no trailing slash.
	 * To be more defensive, we normalize the URLs with `new URL()` first
	 * before doing the comparison.
	 */
	const homeUrl = new URL( getSetting( 'homeUrl' ) ).toString();

	return existingAccounts.find( ( el ) => {
		try {
			const domainUrl = new URL( el.domain );
			return domainUrl.toString() === homeUrl;
		} catch ( e ) {
			return false;
		}
	} );
};

export default getMatchingDomainAccount;
