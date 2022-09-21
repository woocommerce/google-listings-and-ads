/**
 * External dependencies
 */
import { useState, useRef, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import GoogleAdsAccountSection from './google-ads-account-section';
import AudienceSection from '.~/components/paid-ads/audience-section';
import BudgetSection from '.~/components/paid-ads/budget-section';
import BillingCard from '.~/components/paid-ads/billing-card';
import validateForm from '.~/utils/paid-ads/validateForm';
import {
	GOOGLE_ADS_ACCOUNT_STATUS,
	GOOGLE_ADS_BILLING_STATUS,
} from '.~/constants';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 *
 * @typedef {Object} PaidAdsData
 * @property {number|undefined} amount Daily average cost of the paid ads campaign.
 * @property {Array<CountryCode>} countryCodes Audience country codes of the paid ads campaign. Example: 'US'.
 * @property {boolean} isValid Whether the campaign data are valid values.
 * @property {boolean} isReady Whether the campaign data and the billing setting are ready for completing the paid ads setup.
 */

/**
 * Renders sections of Google Ads account, audience, budget, and billing for setting up the paid ads.
 *
 * @param {Object} props React props.
 * @param {(onStatesReceived: PaidAdsData)=>void} props.onStatesReceived Callback to receive the data for setting up paid ads when initial and also when the audience, budget, and billing are updated.
 */
export default function PaidAdsSetupSections( { onStatesReceived } ) {
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { data: targetAudience } = useTargetAudienceFinalCountryCodes();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	const onStatesReceivedRef = useRef();
	onStatesReceivedRef.current = onStatesReceived;

	const initialValues = {
		amount: 0,
		countryCodes: targetAudience,
	};
	const [ campaign, setCampaign ] = useState( {
		...initialValues,
		isValid: ! Object.keys( validateForm( initialValues ) ).length,
		isReady: false,
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
		onStatesReceivedRef.current( {
			...campaign,
			isReady: campaign.isValid && isBillingCompleted,
		} );
	}, [ campaign, isBillingCompleted ] );

	if ( ! targetAudience || ! billingStatus ) {
		return <GoogleAdsAccountSection />;
	}

	return (
		<Form
			initialValues={ {
				amount: 0,
				countryCodes: targetAudience,
			} }
			onChange={ ( _, values, isValid ) => {
				setCampaign( { ...values, isValid } );
			} }
			validate={ validateForm }
		>
			{ ( formProps ) => {
				const { countryCodes } = formProps.values;
				const disabledAudience = ! [
					GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED,
					GOOGLE_ADS_ACCOUNT_STATUS.INCOMPLETE,
				].includes( googleAdsAccount?.status );
				const disabledBudget =
					disabledAudience || countryCodes.length === 0;

				return (
					<>
						<GoogleAdsAccountSection />
						<AudienceSection
							formProps={ formProps }
							disabled={ disabledAudience }
						/>
						<BudgetSection
							formProps={ formProps }
							disabled={ disabledBudget }
						>
							{ ! disabledBudget && <BillingCard /> }
						</BudgetSection>
					</>
				);
			} }
		</Form>
	);
}
