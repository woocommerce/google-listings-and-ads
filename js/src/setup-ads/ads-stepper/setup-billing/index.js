/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';
import { format as formatDate } from '@wordpress/date';
import apiFetch from '@wordpress/api-fetch';

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
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

const useSetupCompleteCallback = ( amount, country ) => {
	const { createNotice } = useDispatchCoreNotices();
	const [ loading, setLoading ] = useState( false );

	const createCampaign = useCallback( () => {
		const date = formatDate( 'Y-m-d', new Date() );
		const options = {
			path: '/wc/gla/ads/campaigns',
			method: 'POST',
			data: {
				name: `Ads Campaign ${ date }`,
				amount: Number( amount ),
				country,
			},
		};

		return apiFetch( options ).catch( () => {
			return Promise.reject(
				__(
					'Unable to launch your ads campaign. Please try again later.',
					'google-listings-and-ads'
				)
			);
		} );
	}, [ amount, country ] );

	const completeAdsSetup = useCallback( () => {
		const options = {
			path: '/wc/gla/ads/setup/complete',
			method: 'POST',
		};
		return apiFetch( options ).catch( () => {
			return Promise.reject(
				__(
					'Unable to complete your ads setup. Please try again later.',
					'google-listings-and-ads'
				)
			);
		} );
	}, [] );

	const handleFinishSetup = useCallback( () => {
		setLoading( true );
		return createCampaign()
			.then( completeAdsSetup )
			.then( () => {
				// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
				const nextPath = getNewPath(
					{ guide: 'campaign-creation-success' },
					'/google/dashboard'
				);
				window.location.href = `/wp-admin/${ nextPath }`;
			} )
			.catch( ( errorMessage ) => {
				createNotice( 'error', errorMessage );
			} )
			.then( () => setLoading( false ) );
	}, [ createCampaign, completeAdsSetup, createNotice ] );

	return [ handleFinishSetup, loading ];
};

const SetupBilling = ( props ) => {
	const {
		formProps: {
			values: { amount, country: countryArr },
		},
	} = props;
	const country = countryArr && countryArr[ 0 ];
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const [ handleSetupComplete, loading ] = useSetupCompleteCallback(
		amount,
		country
	);

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
						onSetupComplete={ handleSetupComplete }
					/>
				) }
			</Section>
			{ billingStatus.status === 'approved' && (
				<StepContentFooter>
					<AppButton
						isPrimary
						loading={ loading }
						onClick={ handleSetupComplete }
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
