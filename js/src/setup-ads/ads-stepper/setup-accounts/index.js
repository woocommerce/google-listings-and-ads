/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StepContent from '../../../components/step-content';
import StepContentHeader from '../../../components/step-content-header';
import StepContentFooter from '../../../components/step-content-footer';
import GoogleAccount from '../../../components/google-account';
import GoogleAdsAccountSection from './google-ads-account-section';

const SetupAccounts = ( props ) => {
	const { onContinue = () => {} } = props;

	// TODO: call backend API to check and set the following to true/false.
	const isContinueButtonDisabled = false;

	return (
		<StepContent>
			<StepContentHeader
				step={ __( 'STEP ONE', 'google-listings-and-ads' ) }
				title={ __(
					'Set up your accounts',
					'google-listings-and-ads'
				) }
				description={ __(
					'Connect your Google account and your Google Ads account to set up a paid Smart Shopping campaign.',
					'google-listings-and-ads'
				) }
			/>
			<GoogleAccount />
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
