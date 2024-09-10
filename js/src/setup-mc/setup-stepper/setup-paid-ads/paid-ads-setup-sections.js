/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import AudienceSection from '.~/components/paid-ads/audience-section';
import BudgetSection from '.~/components/paid-ads/budget-section';
import BillingCard from '.~/components/paid-ads/billing-card';
import SpinnerCard from '.~/components/spinner-card';
import Section from '.~/wcdl/section';
import validateCampaign from '.~/components/paid-ads/validateCampaign';
import clientSession from './clientSession';
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';

/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 *
 * @typedef {Object} PaidAdsData
 * @property {number|undefined} amount Daily average cost of the paid ads campaign.
 * @property {Array<CountryCode>} countryCodes Audience country codes of the paid ads campaign. Example: 'US'.
 * @property {boolean} isValid Whether the campaign data are valid values.
 * @property {boolean} isReady Whether the campaign data and the billing setting are ready for completing the paid ads setup.
 */

const defaultPaidAds = {
	amount: 0,
	countryCodes: [],
	isValid: false,
	isReady: false,
};

/**
 * Resolve the initial paid ads data from the given paid ads data with the loaded target audience.
 * Parts of the resolved data are used in the `initialValues` prop of `Form` component.
 *
 * @param {PaidAdsData} paidAds The paid ads data as the base to be resolved with other states.
 * @param {Array<CountryCode>} targetAudience Country codes of selected target audience.
 * @return {PaidAdsData} The resolved paid ads data.
 */
function resolveInitialPaidAds( paidAds, targetAudience ) {
	const nextPaidAds = { ...paidAds };

	if ( targetAudience ) {
		if ( nextPaidAds.countryCodes === defaultPaidAds.countryCodes ) {
			// Replace the country codes with the loaded target audience only if the reference is
			// the same as the default because the given country codes might be the restored ones.
			nextPaidAds.countryCodes = targetAudience;
		} else {
			// The selected target audience may be changed back and forth during the onboarding flow.
			// Remove countries if any don't exist in the latest state.
			nextPaidAds.countryCodes = nextPaidAds.countryCodes.filter(
				( code ) => targetAudience.includes( code )
			);
		}
	}

	nextPaidAds.isValid = ! Object.keys( validateCampaign( nextPaidAds ) )
		.length;

	return nextPaidAds;
}

/**
 * Renders sections of Google Ads account, audience, budget, and billing for setting up the paid ads.
 *
 * @param {Object} props React props.
 * @param {(onStatesReceived: PaidAdsData)=>void} props.onStatesReceived Callback to receive the data for setting up paid ads when initial and also when the audience, budget, and billing are updated.
 */
export default function PaidAdsSetupSections( { onStatesReceived } ) {
	const { hasGoogleAdsConnection } = useGoogleAdsAccount();
	const { data: targetAudience } = useTargetAudienceFinalCountryCodes();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	const onStatesReceivedRef = useRef();
	onStatesReceivedRef.current = onStatesReceived;

	const [ paidAds, setPaidAds ] = useState( () => {
		// Resolve the starting paid ads data with the campaign data stored in the client session.
		const startingPaidAds = {
			...defaultPaidAds,
			...clientSession.getCampaign(),
		};
		return resolveInitialPaidAds( startingPaidAds, targetAudience );
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
		// clientSession.setCampaign( nextPaidAds );
	}, [ paidAds, isBillingCompleted ] );

	/*
	  Resolve the initial states after the `targetAudience` is loaded.

	  Please note that the loaded `targetAudience` is NOT expected to have further changes
	  in the runtime. If it happens one day and it will need to update <Form>'s internal state
	  with the changed `targetAudience`, please refer to the following practice.
	  - https://github.com/woocommerce/google-listings-and-ads/blob/5b6522ca10ad75556e6b2de7c120cc712aab70b1/js/src/components/free-listings/setup-free-listings/index.js#L120-L134
	  - https://github.com/woocommerce/google-listings-and-ads/blob/5b6522ca10ad75556e6b2de7c120cc712aab70b1/js/src/components/free-listings/setup-free-listings/index.js#L172-L186
	*/
	useEffect( () => {
		setPaidAds( ( currentPaidAds ) =>
			resolveInitialPaidAds( currentPaidAds, targetAudience )
		);
	}, [ targetAudience ] );

	if ( ! targetAudience || ! billingStatus ) {
		return (
			<Section>
				<SpinnerCard />
			</Section>
		);
	}

	const initialValues = {
		countryCodes: paidAds.countryCodes,
		amount: paidAds.amount,
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
				const { countryCodes } = formProps.values;
				const disabledAudience = ! hasGoogleAdsConnection;
				const disabledBudget =
					disabledAudience || countryCodes.length === 0;

				return (
					<>
						<AudienceSection
							formProps={ formProps }
							disabled={ disabledAudience }
							countrySelectHelperText={ __(
								'You can only choose from countries you’ve selected during product listings configuration.',
								'google-listings-and-ads'
							) }
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
