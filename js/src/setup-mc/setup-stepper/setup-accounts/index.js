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

	let isGoogleAccountDisabled = true;
	let isGoogleMCAccountDisabled = true;
	let isContinueButtonDisabled = true;
	let spinner = true;

	const { jetpack } = useJetpackAccount();
	const { google } = useGoogleAccount();
	const { googleMCAccount } = useGoogleMCAccount();

	if ( jetpack ) {
		spinner = false;
		if ( 'yes' == jetpack.active ) {
			isGoogleAccountDisabled = false;
		}
	}

	if ( google ) {
		if ( 'yes' == google.active ) {
			isGoogleMCAccountDisabled = false;
		}
	}

	if ( googleMCAccount ) {
		if ( 'connected' == googleMCAccount.status ) {
			isContinueButtonDisabled = false;
		}
	}

	if ( spinner ) {
		return <AppSpinner />;
	}

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
