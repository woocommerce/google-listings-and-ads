/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import StepContent from '~/components/stepper/step-content';
import StepContentHeader from '~/components/stepper/step-content-header';
import StepContentActions from '~/components/stepper/step-content-actions';
import StepContentFooter from '~/components/stepper/step-content-footer';
import { ConnectedGoogleAccountCard } from '~/components/google-account-card';
import GoogleAdsAccountCard from '~/components/google-ads-account-card';
import FreeAdCredit from '~/components/free-ad-credit';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import AppSpinner from '~/components/app-spinner';
import Section from '~/components/section';
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';

const SetupAccounts = ( props ) => {
	const { onContinue = () => {} } = props;
	const { google } = useGoogleAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { isLinkedToMerchantCenter } = useGoogleAdsAccountReady();

	if ( ! google || ( google.active === 'yes' && ! googleAdsAccount ) ) {
		return <AppSpinner />;
	}

	/**
	 * Here it uses `isLinkedToMerchantCenter` rather than `isGoogleAdsReady`
	 * state because the latter is still true when the linking process fails.
	 * When `isLinkedToMerchantCenter` is true, it already means the connection
	 * process of Ads account is completed.
	 */
	const isContinueButtonDisabled = ! isLinkedToMerchantCenter;

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Set up your accounts',
					'google-listings-and-ads'
				) }
				description={ __(
					'Connect your Google account and your Google Ads account to set up a Performance Max campaign.',
					'google-listings-and-ads'
				) }
			/>
			<Section
				title={ __( 'Connect accounts', 'google-listings-and-ads' ) }
				description={ __(
					'Any campaigns created through this app will appear in your Google Ads account. You will be billed directly through Google.',
					'google-listings-and-ads'
				) }
			>
				<ConnectedGoogleAccountCard
					googleAccount={ google }
					hideAccountSwitch
					helper={ __(
						'This Google account is connected to your store’s product feed.',
						'google-listings-and-ads'
					) }
				/>
				<GoogleAdsAccountCard />
				<FreeAdCredit />
			</Section>
			<StepContentFooter>
				<StepContentActions>
					<AppButton
						isPrimary
						disabled={ isContinueButtonDisabled }
						onClick={ onContinue }
					>
						{ __( 'Continue', 'google-listings-and-ads' ) }
					</AppButton>
				</StepContentActions>
			</StepContentFooter>
		</StepContent>
	);
};

export default SetupAccounts;
