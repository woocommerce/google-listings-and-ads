/**
 * Internal dependencies
 */
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import SpinnerCard from '.~/components/spinner-card';
import Section from '.~/wcdl/section';
import useValidateCampaignWithCountryCodes from '.~/hooks/useValidateCampaignWithCountryCodes';
import PaidAdsSetupForm from './paid-ads-setup-form';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * @typedef {Object} PaidAdsData
 * @property {number|undefined} amount Daily average cost of the paid ads campaign.
 * @property {boolean} isValid Whether the campaign data are valid values.
 * @property {boolean} isReady Whether the campaign data and the billing setting are ready for completing the paid ads setup.
 */

/**
 * Renders sections of Google Ads account, budget and billing for setting up the paid ads.
 * Waits for the validate campaign with country codes to be loaded before rendering the form.
 *
 * @param {Object} props React props.
 * @param {(onStatesReceived: PaidAdsData)=>void} props.onStatesReceived Callback to receive the data for setting up paid ads when initial and also when the budget and billing are updated.
 * @param {Array<CountryCode>|undefined} props.countryCodes Country codes for the campaign.
 */
export default function PaidAdsSetupSections( {
	onStatesReceived,
	countryCodes,
} ) {
	const {
		loading: loadingValidateCampaignWithCountryCodes,
		validateCampaignWithCountryCodes,
	} = useValidateCampaignWithCountryCodes( countryCodes );
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	if ( ! billingStatus || loadingValidateCampaignWithCountryCodes ) {
		return (
			<Section>
				<SpinnerCard />
			</Section>
		);
	}

	return (
		<PaidAdsSetupForm
			onStatesReceived={ onStatesReceived }
			countryCodes={ countryCodes }
			validateCampaign={ validateCampaignWithCountryCodes }
		/>
	);
}
