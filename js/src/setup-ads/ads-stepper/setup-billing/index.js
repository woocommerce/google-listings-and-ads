/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

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
import fallbackBillingUrl from './fallbackBillingUrl';
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';

const SetupBilling = ( props ) => {
	const {
		formProps: { isSubmitting, handleSubmit },
	} = props;

	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	if ( ! billingStatus ) {
		return <AppSpinner />;
	}

	const isApproved =
		billingStatus.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	return (
		<StepContent>
			<StepContentHeader
				title={ __( 'Set up billing', 'google-listings-and-ads' ) }
				description={
					isApproved
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
				{ isApproved ? (
					<BillingSavedCard />
				) : (
					<SetupCard
						billingUrl={
							billingStatus.billing_url || fallbackBillingUrl
						}
						onSetupComplete={ handleSubmit }
					/>
				) }
			</Section>
			{ isApproved && (
				<StepContentFooter>
					<AppButton
						isPrimary
						loading={ isSubmitting }
						onClick={ handleSubmit }
					>
						{ __(
							'Launch paid campaign',
							'google-listings-and-ads'
						) }
					</AppButton>
				</StepContentFooter>
			) }
		</StepContent>
	);
};

export default SetupBilling;
