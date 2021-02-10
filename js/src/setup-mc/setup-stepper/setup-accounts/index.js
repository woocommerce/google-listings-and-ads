/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useJetpackAccount from '.~/hooks/useJetpackAccount';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import AppSpinner from '.~/components/app-spinner';
import WordPressDotComAccount from './wordpressdotcom-account';
import GoogleAccount from './google-account';
import GoogleMCAccount from './google-mc-account';
import StepContent from '../components/step-content';
import StepContentHeader from '../components/step-content-header';
import StepContentFooter from '../components/step-content-footer';

const SetupAccounts = ( props ) => {
	const { onContinue = () => {} } = props;

	const { jetpack } = useJetpackAccount();
	const { google } = useGoogleAccount();
	const { googleMCAccount } = useGoogleMCAccount();

	if ( ! jetpack || ! google || ! googleMCAccount ) {
		return <AppSpinner />;
	}

	const isGoogleAccountDisabled = ! jetpack;
	const isGoogleMCAccountDisabled = ! google;
	const isContinueButtonDisabled = ! googleMCAccount;

	return (
		<StepContent>
			<StepContentHeader
				step={ __( 'STEP ONE', 'google-listings-and-ads' ) }
				title={ __(
					'Set up your accounts',
					'google-listings-and-ads'
				) }
				description={ __(
					'Connect your Wordpress.com account, Google account, and Google Merchant Center account to use Google Listings & Ads.',
					'google-listings-and-ads'
				) }
			/>
			<WordPressDotComAccount />
			<GoogleAccount disabled={ isGoogleAccountDisabled } />
			<GoogleMCAccount disabled={ isGoogleMCAccountDisabled } />
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
