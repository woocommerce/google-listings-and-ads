/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useAdminUrl from '.~/hooks/useAdminUrl';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useAdsSetupCompleteCallback from '.~/hooks/useAdsSetupCompleteCallback';
import { getProductFeedUrl } from '.~/utils/urls';
import { API_NAMESPACE } from '.~/data/constants';
import { GUIDE_NAMES } from '.~/constants';
import AdsCampaign from '.~/components/paid-ads/ads-campaign';

/**
 * Clicking on the "Create a paid ad campaign" button to open the paid ads setup in the onboarding flow.
 *
 * @event gla_onboarding_open_paid_ads_setup_button_click
 */

/**
 * Clicking on the "Complete setup" button to complete the onboarding flow with paid ads.
 *
 * @event gla_onboarding_complete_with_paid_ads_button_click
 * @property {number} budget The budget for the campaign
 * @property {string} audiences The targeted audiences for the campaign
 */

/**
 * Clicking on the skip paid ads button to complete the onboarding flow.
 * The 'unknown' value of properties may means:
 * - the paid ads setup is not opened
 * - the final status has not yet been resolved when recording this event
 * - the status is not available, for example, the billing status is unknown if Google Ads account is not yet connected
 *
 * @event gla_onboarding_complete_button_click
 * @property {string} opened_paid_ads_setup Whether the paid ads setup is opened, e.g. 'yes', 'no'
 * @property {string} google_ads_account_status The connection status of merchant's Google Ads addcount, e.g. 'connected', 'disconnected', 'incomplete'
 * @property {string} billing_method_status aaa, The status of billing method of merchant's Google Ads addcount e.g. 'unknown', 'pending', 'approved', 'cancelled'
 * @property {string} campaign_form_validation Whether the entered paid campaign form data are valid, e.g. 'unknown', 'valid', 'invalid'
 */

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 *
 * @fires gla_onboarding_open_paid_ads_setup_button_click
 * @fires gla_onboarding_complete_with_paid_ads_button_click
 * @fires gla_onboarding_complete_button_click
 */
export default function SetupPaidAds() {
	const adminUrl = useAdminUrl();
	const { createNotice } = useDispatchCoreNotices();
	const [ handleSetupComplete ] = useAdsSetupCompleteCallback();

	const finishOnboardingSetup = async ( event, onBeforeFinish = noop ) => {
		try {
			await onBeforeFinish();
			await apiFetch( {
				path: `${ API_NAMESPACE }/mc/settings/sync`,
				method: 'POST',
			} );
		} catch ( e ) {
			// setCompleting( null );

			createNotice(
				'error',
				__(
					'Unable to complete your setup.',
					'google-listings-and-ads'
				)
			);
		}

		// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getProductFeedUrl( query );
	};

	const handleCompleteClick = async ( event, paidAds ) => {
		const onBeforeFinish = handleSetupComplete.bind(
			null,
			paidAds.amount,
			paidAds.countryCodes
		);
		await finishOnboardingSetup( event, onBeforeFinish );
	};

	return (
		<AdsCampaign
			headerTitle={ __(
				'Create a campaign to advertise your products',
				'google-listings-and-ads'
			) }
			onSkip={ finishOnboardingSetup }
			onContinue={ handleCompleteClick }
			onboardingSetup
		/>
	);
}
