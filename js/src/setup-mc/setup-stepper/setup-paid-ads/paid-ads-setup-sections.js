/**
 * External dependencies
 */
import { useState, useRef, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import BudgetSection from '.~/components/paid-ads/budget-section';
import BillingCard from '.~/components/paid-ads/billing-card';
import SpinnerCard from '.~/components/spinner-card';
import Section from '.~/wcdl/section';
import validateCampaign from '.~/components/paid-ads/validateCampaign';
import clientSession from './clientSession';
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';
import useFetchBudgetRecommendationEffect from '.~/hooks/useFetchBudgetRecommendationEffect';
import getHighestBudget from '.~/utils/getHighestBudget';
import useBudgetRecommendationData from '.~/hooks/useBudgetRecommendationData';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * @typedef {Object} PaidAdsData
 * @property {number|undefined} amount Daily average cost of the paid ads campaign.
 * @property {boolean} isValid Whether the campaign data are valid values.
 * @property {boolean} isReady Whether the campaign data and the billing setting are ready for completing the paid ads setup.
 */

const defaultPaidAds = {
	amount: 0,
	isValid: false,
	isReady: false,
};

/**
 * Resolve the initial paid ads data from the given paid ads data.
 * Parts of the resolved data are used in the `initialValues` prop of `Form` component.
 *
 * @param {PaidAdsData} paidAds The paid ads data as the base to be resolved with other states.
 * @return {PaidAdsData} The resolved paid ads data.
 */
function resolveInitialPaidAds( paidAds ) {
	const nextPaidAds = { ...paidAds };
	nextPaidAds.isValid = ! Object.keys( validateCampaign( nextPaidAds ) )
		.length;

	return nextPaidAds;
}

/**
 * Renders sections of Google Ads account, budget and billing for setting up the paid ads.
 *
 * @param {Object} props React props.
 * @param {(onStatesReceived: PaidAdsData)=>void} props.onStatesReceived Callback to receive the data for setting up paid ads when initial and also when the budget and billing are updated.
 * @param {Array<CountryCode>|undefined} props.countryCodes Country codes for the campaign.
 */
export default function PaidAdsSetupSections( {
	onStatesReceived,
	countryCodes,
} ) {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	const onStatesReceivedRef = useRef();
	onStatesReceivedRef.current = onStatesReceived;

	const [ paidAds, setPaidAds ] = useState( () => {
		// Resolve the starting paid ads data with the campaign data stored in the client session.
		const startingPaidAds = {
			...defaultPaidAds,
			...clientSession.getCampaign(),
		};
		return resolveInitialPaidAds( startingPaidAds );
	} );

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const { country, dailyBudget, multipleRecommendations, loading } =
		useBudgetRecommendationData( countryCodes );

	/*
	  If a merchant has not yet finished the billing setup, the billing status will be
	  updated by `useAutoCheckBillingStatusEffect` hook in `BillingSetupCard` component
	  till it gets completed.

	  Or, if the billing setup is already finished, the loaded `billingStatus.status`
		will already be 'approved' without passing through the above hook and component.

	  Therefore, in order to ensure the parent component can continue the setup from
	  any billing status, it only needs to watch the `isBillingCompleted` eventually
	  to wait for the fulfilled 'approved' status, and then propagate it to the parent.

	  For example, refresh page during onboarding flow after the billing setup is finished.
	*/
	useEffect( () => {
		if ( ! loading ) {
			const sessionCampaign = clientSession.getCampaign();
			const sessionAmount = sessionCampaign?.amount;

			const nextPaidAds = {
				...paidAds,
				amount: sessionAmount || dailyBudget,
				isReady: paidAds.isValid && isBillingCompleted,
			};

			onStatesReceivedRef.current( nextPaidAds );
			clientSession.setCampaign( nextPaidAds );
		}
	}, [ dailyBudget, paidAds, isBillingCompleted, loading ] );

	if ( ! billingStatus || loading ) {
		return (
			<Section>
				<SpinnerCard />
			</Section>
		);
	}

	const initialValues = {
		amount: clientSession.getCampaign()?.amount || dailyBudget,
	};

	return (
		<Form
			initialValues={ initialValues }
			onChange={ ( _, values, isValid ) => {
				setPaidAds( { ...paidAds, ...values, isValid } );

				if ( isValid ) {
					clientSession.setCampaign( values );
				}
			} }
			validate={ validateCampaign }
		>
			{ ( formProps ) => {
				return (
					<BudgetSection
						formProps={ formProps }
						countryCodes={ countryCodes }
						dailyBudget={ dailyBudget }
						country={ country }
						isMultiple={ multipleRecommendations }
					>
						<BillingCard />
					</BudgetSection>
				);
			} }
		</Form>
	);
}
