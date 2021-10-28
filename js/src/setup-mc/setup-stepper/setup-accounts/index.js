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
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import WordPressDotComAccount from './wordpressdotcom-account';
import GoogleAccountCard from '.~/components/google-account-card';
import GoogleMCAccount from './google-mc-account';
import Faqs from './faqs';
import WPComAccountCard from './wordpressdotcom-account/wpcom-account-card';

const SetupAccounts = ( props ) => {
	const { onContinue = () => {} } = props;
	const { jetpack } = useJetpackAccount();
	const { google, scope } = useGoogleAccount();
	const { googleMCAccount } = useGoogleMCAccount();

	if (
		! jetpack ||
		( jetpack.active === 'yes' &&
			( ! google || ( google.active === 'yes' && ! googleMCAccount ) ) )
	) {
		return <AppSpinner />;
	}

	const isGoogleAccountDisabled = jetpack?.active !== 'yes';
	const isGoogleConnected = google?.active === 'yes';
	const isGoogleMCAccountDisabled = ! (
		isGoogleConnected && scope.gmcRequired
	);
	const isContinueButtonDisabled = googleMCAccount?.status !== 'connected';

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Set up your accounts',
					'google-listings-and-ads'
				) }
				description={ __(
					'Connect your WordPress.com account, Google account, and Google Merchant Center account to use Google Listings & Ads.',
					'google-listings-and-ads'
				) }
			/>
			<Section
				title={ __( 'Connect accounts', 'google-listings-and-ads' ) }
				description={ __(
					'The following accounts are required to use the Google Listings & Ads plugin.',
					'google-listings-and-ads'
				) }
			>
				<VerticalGapLayout size="large">
					<WordPressDotComAccount />
					<WPComAccountCard />
					<GoogleAccountCard disabled={ isGoogleAccountDisabled } />
				</VerticalGapLayout>
			</Section>
			<GoogleMCAccount disabled={ isGoogleMCAccountDisabled } />
			<Faqs />
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
