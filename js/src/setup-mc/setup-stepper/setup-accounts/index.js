/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import WordPressDotComAccount from './wordpressdotcom-account';
import GoogleAccount from './google-account';
import GoogleMCAccount from './google-mc-account';
import './index.scss';

const SetupAccounts = ( props ) => {
	const { onContinue = () => {} } = props;

	// TODO: set Continue button disabled to false when all three accounts are connected,
	// which we can only know by calling backend API.
	const isContinueButtonDisabled = true;

	return (
		<div className="gla-setup-accounts">
			<header className="gla-setup-accounts__header">
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
			</header>
			<WordPressDotComAccount />
			<GoogleAccount />
			<GoogleMCAccount />
			<div className="actions">
				<Button
					isPrimary
					disabled={ isContinueButtonDisabled }
					onClick={ onContinue }
				>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</Button>
			</div>
		</div>
	);
};

export default SetupAccounts;
