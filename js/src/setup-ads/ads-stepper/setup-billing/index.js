/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import AppSpinner from '.~/components/app-spinner';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import Section from '.~/wcdl/section';
import SetupCard from './setup-card';
import BillingSavedCard from './billing-saved-card';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppButton from '.~/components/app-button';

const SetupBilling = () => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	if ( ! billingStatus ) {
		return <AppSpinner />;
	}

	// TODO: should we really create ads campaign in Step 3 here? or maybe just redirect to dashboard page?
	const handleLaunchClick = () => {
		getHistory().push(
			getNewPath(
				{ guide: 'campaign-creation-success' },
				'/google/dashboard'
			)
		);
	};

	return (
		<StepContent>
			<StepContentHeader
				step={ __( 'STEP THREE', 'google-listings-and-ads' ) }
				title={ __( 'Set up billing', 'google-listings-and-ads' ) }
				description={
					billingStatus.status === 'approved'
						? __(
								'You will be billed directly by Google Ads, and you only pay when you get results.',
								'google-listings-and-ads'
						  )
						: __(
								'In order to launch your paid campaign, your billing information is required. You will be billed directly by Google and only pay when someone clicks on your ad.',
								'google-listings-and-ads'
						  )
				}
			/>
			<Section
				title={ __(
					'Payment info through Google Ads',
					'google-listings-and-ads'
				) }
			>
				{ billingStatus.status === 'approved' ? (
					<BillingSavedCard />
				) : (
					<SetupCard
						billingUrl={
							billingStatus.billing_url ||
							'http://www.google.com/'
						}
					/>
				) }
			</Section>
			{ billingStatus.status === 'approved' && (
				<StepContentFooter>
					<AppButton isPrimary onClick={ handleLaunchClick }>
						{ __( 'Launch campaign', 'google-listings-and-ads' ) }
					</AppButton>
				</StepContentFooter>
			) }
		</StepContent>
	);
};

export default SetupBilling;
