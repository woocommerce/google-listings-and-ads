/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsCardLayout from '../../../components/settings-card-layout';
import './index.scss';

const SetupAccounts = () => {
	const handleContinueClick = () => {};

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
			<SettingsCardLayout
				title={ __( 'WordPress.com', 'google-listings-and-ads' ) }
				description={ __(
					'WooCommerce requires a WordPress.com account to connect to Google.',
					'google-listings-and-ads'
				) }
			>
				<div className="gla-setup-accounts__connect-account">
					<span>
						{ __(
							'Connect your WordPress.com account',
							'google-listings-and-ads'
						) }
					</span>
					<Button isSecondary>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</Button>
				</div>
			</SettingsCardLayout>
			<SettingsCardLayout
				title={ __( 'Google account', 'google-listings-and-ads' ) }
				description={ __(
					'WooCommerce uses your Google account to sync with Google Merchant Center and Google Ads.',
					'google-listings-and-ads'
				) }
			>
				<div className="gla-setup-accounts__connect-account">
					<span>
						{ __(
							'Connect your Google account',
							'google-listings-and-ads'
						) }
					</span>
					<Button isSecondary>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</Button>
				</div>
			</SettingsCardLayout>
			<SettingsCardLayout
				title={ __(
					'Google Merchant Center account',
					'google-listings-and-ads'
				) }
				description={ __(
					'WooCommerce products synced to your Merchant Center product feed will allow you to list your products on Google.',
					'google-listings-and-ads'
				) }
			>
				<div className="gla-setup-accounts__connect-account">
					<span>
						{ __(
							'Connect your Merchant Center',
							'google-listings-and-ads'
						) }
					</span>
					<Button isSecondary>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</Button>
				</div>
			</SettingsCardLayout>
			<div className="actions">
				<Button isPrimary onClick={ handleContinueClick }>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</Button>
			</div>
		</div>
	);
};

export default SetupAccounts;
