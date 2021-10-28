/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AccountCard from '.~/components/account-card';
import GoogleAdsAccountSection from './google-ads-account-section';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import AppSpinner from '.~/components/app-spinner';
import Section from '.~/wcdl/section';
import './index.scss';

const SetupAccounts = ( props ) => {
	const { onContinue = () => {} } = props;
	const { google } = useGoogleAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();

	if ( ! google || ( google.active === 'yes' && ! googleAdsAccount ) ) {
		return <AppSpinner />;
	}

	const isContinueButtonDisabled = ! googleAdsAccount.id;

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Set up your accounts',
					'google-listings-and-ads'
				) }
				description={ __(
					'Connect your Google account and your Google Ads account to set up a paid Smart Shopping campaign.',
					'google-listings-and-ads'
				) }
			/>
			<Section
				title={ __( 'Google account', 'google-listings-and-ads' ) }
				description={ __(
					'WooCommerce uses your Google account to sync with Google Merchant Center and Google Ads.',
					'google-listings-and-ads'
				) }
			>
				<AccountCard
					className="gla-setup-ads-accounts__google-card"
					appearance={ { title: google.email } }
					description={ __(
						'This Google account is connected to your store’s product feed.',
						'google-listings-and-ads'
					) }
				/>
			</Section>
			<GoogleAdsAccountSection />
			<StepContentFooter>
				<Button
					isPrimary
					disabled={ isContinueButtonDisabled }
					onClick={ onContinue }
				>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</Button>
			</StepContentFooter>
		</StepContent>
	);
};

export default SetupAccounts;
