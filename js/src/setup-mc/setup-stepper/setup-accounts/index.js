/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

const SetupAccounts = () => {
	return (
		<div className="gla-setup-accounts">
			<div className="header">
				<p className="step">
					{ __( 'STEP ONE', 'google-listings-and-ads' ) }
				</p>
				<h1>
					{ __( 'Set up your accounts', 'google-listings-and-ads' ) }
				</h1>
				<p className="description">
					{ __(
						'Connect your Wordpress.com account, Google account, and Google Merchant Center account to use Google Listings & Ads.',
						'google-listings-and-ads'
					) }
				</p>
			</div>
		</div>
	);
};

export default SetupAccounts;
