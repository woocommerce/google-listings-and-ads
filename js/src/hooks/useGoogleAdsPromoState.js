/**
 * Internal dependencies
 */
import { getCreateCampaignUrl, getOnboardingUrl } from '~/utils/urls';
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';
import useHasRecentAdSpend from '~/hooks/useHasRecentAdSpend';

/**
 * @typedef {Object} GoogleAdsPromoState
 * @property {boolean} isResolving Whether the merchant state is still being determined.
 * @property {boolean} isEligible  Whether the promo should be shown for the current merchant. `false` while resolving or when the merchant has recent Ads spend.
 * @property {boolean} isReady     Whether the Google Ads account is ready (connected, claimed, access granted). Drives the CTA/copy state: `false` → "Get started", `true` → "Launch a campaign". `INCOMPLETE` accounts are treated as ready.
 * @property {string}  ctaUrl      Destination for the CTA: onboarding URL when not ready, campaign-creation URL when ready.
 */

/**
 * Merchant-state gating for the Google Ads promo placement.
 *
 * This placement is Google Ads only (no Merchant Center), so the
 * onboarded/not-onboarded split keys off Ads readiness rather than an MC
 * connection. Merchants with recent Ads spend are suppressed.
 *
 * @return {GoogleAdsPromoState} The resolution state, eligibility, readiness, and CTA destination.
 */
const useGoogleAdsPromoState = () => {
	const { isGoogleAdsReady } = useGoogleAdsAccountReady();
	const { hasAdSpend, hasFinishedResolution: hasResolvedRecentAdSpend } =
		useHasRecentAdSpend();

	const isResolving = isGoogleAdsReady === null || ! hasResolvedRecentAdSpend;
	const isReady = isGoogleAdsReady === true;
	const isEligible = ! isResolving && ! hasAdSpend;
	const ctaUrl = isReady ? getCreateCampaignUrl() : getOnboardingUrl();

	return {
		isResolving,
		isEligible,
		isReady,
		ctaUrl,
	};
};

export default useGoogleAdsPromoState;
