/**
 * Internal dependencies
 */
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import BudgetSection from '.~/components/paid-ads/budget-section';
import BillingCard from '.~/components/paid-ads/billing-card';
import SpinnerCard from '.~/components/spinner-card';
import Section from '.~/wcdl/section';
import CampaignPreviewCard from '.~/components/paid-ads/campaign-preview/campaign-preview-card';
import { useAdaptiveFormContext } from '.~/components/adaptive-form';

/**
 *
 * @typedef {import('.~/data/actions').Campaign} Campaign
 */

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

/**
 * Renders sections of Google Ads account, budget and billing for setting up the paid ads.
 *
 * @param {Object} props React props.
 * @param {Array<CountryCode>|undefined} props.countryCodes Country codes for the campaign.
 * @param {boolean} [props.showCampaignPreviewCard=false] Whether to show the campaign preview card.
 * @param {boolean} props.showBillingCard Whether to show the billing card.
 */
export default function PaidAdsSetupSections( {
	countryCodes,
	showCampaignPreviewCard = false,
	showBillingCard,
} ) {
	const formContext = useAdaptiveFormContext();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	if ( ! billingStatus ) {
		return (
			<Section>
				<SpinnerCard />
			</Section>
		);
	}

	return (
		<BudgetSection formProps={ formContext } countryCodes={ countryCodes }>
			{ showBillingCard && (
				<BillingCard billingStatus={ billingStatus } />
			) }

			{ showCampaignPreviewCard && <CampaignPreviewCard /> }
		</BudgetSection>
	);
}
