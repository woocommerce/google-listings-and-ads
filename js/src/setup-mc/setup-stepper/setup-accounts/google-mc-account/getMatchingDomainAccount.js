/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

const getMatchingDomainAccount = ( existingAccounts = [] ) => {
	const homeUrl = getSetting( 'homeUrl' );

	/**
	 * `homeUrl` has a trailing slash,
	 * while `el.domain` has no trailing slash,
	 * so we append a slash before we do the comparison.
	 */
	return existingAccounts.find( ( el ) => el.domain + '/' === homeUrl );
};

export default getMatchingDomainAccount;
