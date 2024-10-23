/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentActions from '.~/components/stepper/step-content-actions';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import { ConnectedGoogleAccountCard } from '.~/components/google-account-card';
import GoogleAdsAccountCard from '.~/components/google-ads-account-card';
import FreeAdCredit from './free-ad-credit';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useFreeAdCredit from '.~/hooks/useFreeAdCredit';
import AppSpinner from '.~/components/app-spinner';
import Section from '.~/wcdl/section';
import useGoogleAdsAccountReady from '.~/hooks/useGoogleAdsAccountReady';

const SetupAccounts = ( props ) => {
	const { onContinue = () => {} } = props;
	const { google } = useGoogleAccount();
	const { googleAdsAccount } = useGoogleAdsAccount();
	const hasFreeAdCredit = useFreeAdCredit();
	const isGoogleAdsReady = useGoogleAdsAccountReady();

	if ( ! google || ( google.active === 'yes' && ! googleAdsAccount ) ) {
		return <AppSpinner />;
	}

	const isContinueButtonDisabled = ! isGoogleAdsReady;

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Set up your accounts',
					'google-listings-and-ads'
				) }
				description={ __(
					'Connect your Google account and your Google Ads account to set up a paid Performance Max campaign.',
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
				<VerticalGapLayout size="large">
					<ConnectedGoogleAccountCard
						googleAccount={ google }
						hideAccountSwitch
						helper={ __(
							'This Google account is connected to your store’s product feed.',
							'google-listings-and-ads'
						) }
					/>
					<GoogleAdsAccountCard />
					{ hasFreeAdCredit && <FreeAdCredit /> }
				</VerticalGapLayout>
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
