/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { format as formatDate } from '@wordpress/date';

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
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

const SetupBilling = ( props ) => {
	const {
		formProps: {
			values: { amount, country: countryArr },
		},
	} = props;
	const country = countryArr && countryArr[ 0 ];
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const [ fetchCreateCampaign, { loading } ] = useApiFetchCallback();
	const { createNotice } = useDispatchCoreNotices();

	const handleLaunchCampaign = useCallback( async () => {
		try {
			const date = formatDate( 'Y-m-d', new Date() );

			await fetchCreateCampaign( {
				path: '/wc/gla/ads/campaigns',
				method: 'POST',
				data: {
					name: `Ads Campaign ${ date }`,
					amount: Number( amount ),
					country,
				},
			} );

			getHistory().push(
				getNewPath(
					{ guide: 'campaign-creation-success' },
					'/google/dashboard'
				)
			);
		} catch ( e ) {
			createNotice(
				'error',
				__(
					'Unable to launch your ads campaign. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	}, [ amount, country, createNotice, fetchCreateCampaign ] );

	if ( ! billingStatus ) {
		return <AppSpinner />;
	}

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
						billingUrl={ billingStatus.billing_url }
						onSetupComplete={ handleLaunchCampaign }
					/>
				) }
			</Section>
			{ billingStatus.status === 'approved' && (
				<StepContentFooter>
					<AppButton
						isPrimary
						loading={ loading }
						onClick={ handleLaunchCampaign }
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
