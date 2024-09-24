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
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';

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
 * Renders sections of Google Ads account, budget and billing for setting up the paid ads.
 * Waits for the validate campaign with country codes function to be loaded before rendering the form.
 *
 * @param {Object} props React props.
 * @param {(onStatesReceived: PaidAdsData)=>void} props.onStatesReceived Callback to receive the data for setting up paid ads when initial and also when the budget and billing are updated.
 * @param {Array<CountryCode>|undefined} props.countryCodes Country codes for the campaign.
 */
export default function PaidAdsSetupSections( {
	onStatesReceived,
	countryCodes,
} ) {
	const { validateCampaignWithCountryCodes, loaded } =
		useValidateCampaignWithCountryCodes( countryCodes );
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const onStatesReceivedRef = useRef();
	onStatesReceivedRef.current = onStatesReceived;

	const [ paidAds, setPaidAds ] = useState( () => {
		// Resolve the starting paid ads data with the campaign data stored in the client session.
		const startingPaidAds = {
			...defaultPaidAds,
			...clientSession.getCampaign(),
		};
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
		amount: paidAds.amount,
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
					<BudgetSection
						formProps={ formProps }
						countryCodes={ countryCodes }
					>
						<BillingCard />
					</BudgetSection>
				);
			} }
		</Form>
	);
}
