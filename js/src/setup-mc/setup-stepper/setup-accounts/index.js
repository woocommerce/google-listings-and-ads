/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useJetpackAccount from '.~/hooks/useJetpackAccount';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import AppButton from '.~/components/app-button';
import AppSpinner from '.~/components/app-spinner';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import Section from '.~/wcdl/section';
import AppDocumentationLink from '.~/components/app-documentation-link';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import WPComAccountCard from '.~/components/wpcom-account-card';
import GoogleAccountCard from '.~/components/google-account-card';
import GoogleMCAccountCard from '.~/components/google-mc-account-card';
import GoogleAdsAccountCard from '.~/components/google-ads-account-card';
import Faqs from './faqs';
import './index.scss';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import StepContentActions from '.~/components/stepper/step-content-actions';

/**
 * Renders the disclaimer of Comparison Shopping Service (CSS).
 *
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-accounts', link_id: 'comparison-shopping-services', href: 'https://support.google.com/merchants/topic/9080307' }`
 * @fires gla_documentation_link_click with `{ context: 'setup-mc-accounts', link_id: 'comparison-shopping-partners-find-a-partner', href: 'https://comparisonshoppingpartners.withgoogle.com/find_a_partner/' }`
 */
const GoogleMCDisclaimer = () => {
	return (
		<>
			<p>
				{ createInterpolateElement(
					__(
						'If you are in the European Economic Area or Switzerland, your Merchant Center account must be associated with a Comparison Shopping Service (CSS). Please find more information at <link>Google Merchant Center Help</link> website.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="setup-mc-accounts"
								linkId="comparison-shopping-services"
								href="https://support.google.com/merchants/topic/9080307"
							/>
						),
					}
				) }
			</p>
			<p>
				{ createInterpolateElement(
					__(
						'If you create a new Merchant Center account through this application, it will be associated with Google Shopping, Google’s CSS, by default. You can change the CSS associated with your account at any time. Please find more information about our CSS Partners <link>here</link>.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context="setup-mc-accounts"
								linkId="comparison-shopping-partners-find-a-partner"
								href="https://comparisonshoppingpartners.withgoogle.com/find_a_partner/"
							/>
						),
					}
				) }
			</p>
			<p>
				{ __(
					'Once you have set up your Merchant Center account you can use our onboarding tool regardless of which CSS you use.',
					'google-listings-and-ads'
				) }
			</p>
		</>
	);
};

const SetupAccounts = ( props ) => {
	const { onContinue = () => {} } = props;
	const { jetpack } = useJetpackAccount();
	const { google, scope } = useGoogleAccount();
	const { googleMCAccount, isPreconditionReady: isGMCPreconditionReady } =
		useGoogleMCAccount();
	const { hasFinishedResolution, hasGoogleAdsConnection } =
		useGoogleAdsAccount();
	const { hasAccess, step } = useGoogleAdsAccountStatus();

	/**
	 * When jetpack is loading, or when google account is loading,
	 * or when GMC account is loading, we display the AppSpinner.
	 *
	 * The account loading is in sequential manner, one after another.
	 *
	 * Note that we can't use hasFinishedResolution here.
	 * If we do, when GMC account is connected, resolution would be fired again,
	 * and the whole page would display this AppSpinner, which is not desired.
	 */
	const isLoadingJetpack = ! jetpack;
	const isLoadingGoogle = jetpack?.active === 'yes' && ! google;
	const isLoadingGoogleMCAccount =
		google?.active === 'yes' && scope.gmcRequired && ! googleMCAccount;

	if ( isLoadingJetpack || isLoadingGoogle || isLoadingGoogleMCAccount ) {
		return <AppSpinner />;
	}

	const isGoogleAccountDisabled = jetpack?.active !== 'yes';

	// Ads is ready when we have a connection and verified and verified access.
	// Billing is not required, and the 'link_merchant' step will be resolved
	// when the MC the account is connected.
	const isGoogleAdsReady =
		hasGoogleAdsConnection &&
		hasAccess &&
		[ '', 'billing', 'link_merchant' ].includes( step );

	// MC is ready when we have a connection and preconditions are met.
	// The `link_ads` step will be resolved when the Ads account is connected
	// since these can be connected in any order.
	const isGoogleMCReady =
		isGMCPreconditionReady &&
		( googleMCAccount?.status === 'connected' ||
			( googleMCAccount?.status === 'incomplete' &&
				googleMCAccount?.step === 'link_ads' ) );

	const isContinueButtonDisabled = ! (
		hasFinishedResolution &&
		isGoogleAdsReady &&
		isGoogleMCReady
	);

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Set up your accounts',
					'google-listings-and-ads'
				) }
				description={ __(
					'Connect the accounts required to use Google for WooCommerce.',
					'google-listings-and-ads'
				) }
			/>
			<Section
				className="gla-wp-google-accounts-section"
				title={ __( 'Connect accounts', 'google-listings-and-ads' ) }
				description={ __(
					'The following accounts are required to use the Google for WooCommerce plugin.',
					'google-listings-and-ads'
				) }
			>
				<VerticalGapLayout size="large">
					<WPComAccountCard jetpack={ jetpack } />
					<GoogleAccountCard disabled={ isGoogleAccountDisabled } />
					<GoogleAdsAccountCard />
				</VerticalGapLayout>
			</Section>
			<Section
				className="gla-google-mc-account-section"
				description={ <GoogleMCDisclaimer /> }
				disabledLeft={ ! isGMCPreconditionReady }
			>
				<GoogleMCAccountCard />
			</Section>

			<StepContentActions>
				<StepContentFooter>
					<AppButton
						isPrimary
						disabled={ isContinueButtonDisabled }
						onClick={ onContinue }
					>
						{ __( 'Continue', 'google-listings-and-ads' ) }
					</AppButton>
				</StepContentFooter>
				<Faqs />
			</StepContentActions>
		</StepContent>
	);
};

export default SetupAccounts;
