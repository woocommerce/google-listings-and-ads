/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Only used temporarily for beta testing purpose. For production roll out, this should be not needed.
 */
const betaExistingProductListingsStatement = __(
	`We've detected that your store may have some existing product listings in Google. Because this extension is still in beta, we don't want to disrupt any active listings, so you cannot continue to setup this extension at this point. Thanks for participating in our beta test!`,
	'google-listings-and-ads'
);

export default betaExistingProductListingsStatement;
