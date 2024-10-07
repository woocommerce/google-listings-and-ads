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
import validateCampaign from '.~/components/paid-ads/validateCampaign';
import clientSession from './clientSession';
import CampaignPreviewCard from '.~/components/paid-ads/campaign-preview/campaign-preview-card';
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';

/**
 *
 * @typedef {import('.~/data/actions').Campaign} Campaign
 */

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * @typedef {import('./paid-ads-setup-sections').PaidAdsData} PaidAdsData
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
 * @param {Campaign} [props.campaign] Campaign data to be edited. If not provided, this component will show campaign creation UI.
 * @param {boolean} [props.showCampaignPreviewCard=false] Whether to show the campaign preview card.
 * @param {boolean} [props.loadCampaignFromClientSession=false] Whether to load the campaign data from the client session.
 * @param {number} props.recommendedBudget The recommended budget.
 */
export default function PaidAdsSetupForm( {
	onStatesReceived,
	countryCodes,
	campaign,
	loadCampaignFromClientSession,
	showCampaignPreviewCard = false,
	recommendedBudget,
} ) {
	const isCreation = ! campaign;
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	const onStatesReceivedRef = useRef();
	onStatesReceivedRef.current = onStatesReceived;

	const [ paidAds, setPaidAds ] = useState( () => {
		let startingPaidAds = {
			...defaultPaidAds,
		};

		// If we are creating a new campaign, set the amount with the recommended daily amount.
		if ( ! campaign ) {
			startingPaidAds = {
				...startingPaidAds,
				amount: recommendedBudget,
			};
		}

		// Resolve the starting paid ads data with the campaign data stored in the client session if any.
		if ( loadCampaignFromClientSession ) {
			const initialAmount = Math.max(
				clientSession.getCampaign()?.amount || 0,
				recommendedBudget
			);

			startingPaidAds = {
				...startingPaidAds,
				...clientSession.getCampaign(),
				amount: initialAmount,
			};
		}

		return resolveInitialPaidAds( startingPaidAds );
	} );

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

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
		const nextPaidAds = {
			...paidAds,
			isReady: paidAds.isValid && isBillingCompleted,
		};
		onStatesReceivedRef.current( nextPaidAds );
		clientSession.setCampaign( nextPaidAds );
	}, [ paidAds, isBillingCompleted ] );

	const initialValues = {
		amount: isCreation ? paidAds.amount : campaign.amount,
	};

	return (
		<Form
			initialValues={ initialValues }
			onChange={ ( _, values, isValid ) => {
				setPaidAds( { ...paidAds, ...values, isValid } );
			} }
			validate={ validateCampaign }
		>
			{ ( formProps ) => {
				return (
					<BudgetSection
						formProps={ formProps }
						countryCodes={ countryCodes }
					>
						<BillingCard />
						{ showCampaignPreviewCard && <CampaignPreviewCard /> }
					</BudgetSection>
				);
			} }
		</Form>
	);
}
