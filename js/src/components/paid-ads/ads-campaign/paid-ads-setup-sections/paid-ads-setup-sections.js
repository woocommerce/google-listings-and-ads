/**
 * Internal dependencies
 */
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import SpinnerCard from '.~/components/spinner-card';
import Section from '.~/wcdl/section';
import PaidAdsSetupForm from './paid-ads-setup-form';
import useFetchBudgetRecommendation from '.~/hooks/useFetchBudgetRecommendation';
import getHighestBudget from '.~/utils/getHighestBudget';

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

/**
 * Renders sections of Google Ads account, budget and billing for setting up the paid ads.
 *
 * @param {Object} props React props.
 * @param {(onStatesReceived: PaidAdsData)=>void} props.onStatesReceived Callback to receive the data for setting up paid ads when initial and also when the budget and billing are updated.
 * @param {Array<CountryCode>|undefined} props.countryCodes Country codes for the campaign.
 * @param {Campaign} [props.campaign] Campaign data to be edited. If not provided, this component will show campaign creation UI.
 * @param {boolean} [props.showCampaignPreviewCard=false] Whether to show the campaign preview card.
 * @param {boolean} [props.loadCampaignFromClientSession=false] Whether to load the campaign data from the client session.
 */
export default function PaidAdsSetupSections( props ) {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { hasFinishedResolution, data } = useFetchBudgetRecommendation(
		props.countryCodes
	);

	let recommendedBudget = 0;
	if ( data ) {
		const { recommendations } = data;
		const { daily_budget: dailyBudget } =
			getHighestBudget( recommendations );
		recommendedBudget = dailyBudget;
	}

	if ( ! billingStatus || ! hasFinishedResolution ) {
		return (
			<Section>
				<SpinnerCard />
			</Section>
		);
	}

	return (
		<PaidAdsSetupForm
			recommendedBudget={ recommendedBudget || 0 }
			{ ...props }
		/>
	);
}
