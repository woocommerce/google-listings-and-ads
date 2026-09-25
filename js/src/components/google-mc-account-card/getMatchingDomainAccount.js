/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

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
