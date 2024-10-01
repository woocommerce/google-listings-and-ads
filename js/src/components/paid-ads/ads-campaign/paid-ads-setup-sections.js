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
import useValidateCampaignWithCountryCodes from '.~/hooks/useValidateCampaignWithCountryCodes';
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
 *
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
 * Renders sections of Google Ads account, budget and billing for setting up the paid ads.
 * Waits for the validate campaign with country codes function to be loaded before rendering the form.
 *
 * @param {Object} props React props.
 * @param {(onStatesReceived: PaidAdsData)=>void} props.onStatesReceived Callback to receive the data for setting up paid ads when initial and also when the budget and billing are updated.
 * @param {Campaign} [props.campaign] Campaign data to be edited. If not provided, this component will show campaign creation UI.
 * @param {boolean} [props.showCampaignPreviewCard=false] Whether to show the campaign preview card.
 * @param {boolean} [props.loadCampaignFromClientSession=false] Whether to load the campaign data from the client session.
 */
export default function PaidAdsSetupSections( {
	onStatesReceived,
	campaign,
	loadCampaignFromClientSession,
	showCampaignPreviewCard = false,
} ) {
	const { validateCampaignWithCountryCodes, loaded } =
		useValidateCampaignWithCountryCodes();
	const isCreation = ! campaign;
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const onStatesReceivedRef = useRef();
	onStatesReceivedRef.current = onStatesReceived;

	const [ paidAds, setPaidAds ] = useState( () => {
		// Resolve the starting paid ads data with the campaign data stored in the client session.
		let startingPaidAds = {
			...defaultPaidAds,
		};

		if ( loadCampaignFromClientSession ) {
			startingPaidAds = {
				...startingPaidAds,
				...clientSession.getCampaign(),
			};
		}
		return startingPaidAds;
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
		const isValid = ! Object.keys(
			validateCampaignWithCountryCodes( paidAds )
		).length;
		const nextPaidAds = {
			...paidAds,
			isValid,
			isReady: isValid && isBillingCompleted,
		};

		onStatesReceivedRef.current( nextPaidAds );
		clientSession.setCampaign( nextPaidAds );
	}, [ paidAds, isBillingCompleted, validateCampaignWithCountryCodes ] );

	if ( ! billingStatus || ! loaded ) {
		return (
			<Section>
				<SpinnerCard />
			</Section>
		);
	}

	const initialValues = {
		amount: isCreation ? paidAds.amount : campaign.amount,
	};

	return (
		<Form
			initialValues={ initialValues }
			onChange={ ( _, values, isValid ) => {
				setPaidAds( { ...paidAds, ...values, isValid } );
			} }
			validate={ validateCampaignWithCountryCodes }
		>
			{ ( formProps ) => {
				return (
					<BudgetSection formProps={ formProps }>
						<BillingCard />
						{ showCampaignPreviewCard && <CampaignPreviewCard /> }
					</BudgetSection>
				);
			} }
		</Form>
	);
}
