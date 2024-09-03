/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { select } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';
import { noop, merge } from 'lodash';

/**
 * Internal dependencies
 */
import useAdminUrl from '.~/hooks/useAdminUrl';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useAdsSetupCompleteCallback from '.~/hooks/useAdsSetupCompleteCallback';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import FaqsSection from '.~/components/paid-ads/faqs-section';
import AppButton from '.~/components/app-button';
import PaidAdsFeaturesSection from '.~/components/paid-ads/ads-campaign/paid-ads-features-section';
import PaidAdsSetupSections from '.~/components/paid-ads/ads-campaign/paid-ads-setup-sections';
import { getProductFeedUrl } from '.~/utils/urls';
import clientSession from '.~/components/paid-ads/ads-campaign/clientSession';
import { API_NAMESPACE, STORE_KEY } from '.~/data/constants';
import { GUIDE_NAMES } from '.~/constants';
import AdsCampaign from '.~/components/paid-ads/ads-campaign';

const ACTION_COMPLETE = 'complete-ads';
const ACTION_SKIP = 'skip-ads';

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
	const { googleAdsAccount, hasGoogleAdsConnection } = useGoogleAdsAccount();
	const [ handleSetupComplete ] = useAdsSetupCompleteCallback();
	const [ showPaidAdsSetup, setShowPaidAdsSetup ] = useState( () =>
		clientSession.getShowPaidAdsSetup( false )
	);
	const [ completing, setCompleting ] = useState( null );

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

	// return (
	// 	<StepContent>
	// 		<StepContentHeader
	// 			title={ __(
	// 				'Create a campaign to advertise your products',
	// 				'google-listings-and-ads'
	// 			) }
	// 			description={ __(
	// 				'You’re ready to set up a Performance Max campaign to drive more sales with ads. Your products will be included in the campaign after they’re approved.',
	// 				'google-listings-and-ads'
	// 			) }
	// 		/>

	// 		<PaidAdsFeaturesSection
	// 			hideBudgetContent={ ! hasGoogleAdsConnection }
	// 			hideFooterButtons={
	// 				! hasGoogleAdsConnection || showPaidAdsSetup
	// 			}
	// 			skipButton={ createSkipButton(
	// 				__( 'Skip this step for now', 'google-listings-and-ads' )
	// 			) }
	// 			continueButton={
	// 				<AppButton
	// 					isPrimary
	// 					text={ __(
	// 						'Create campaign',
	// 						'google-listings-and-ads'
	// 					) }
	// 					disabled={ completing === ACTION_SKIP }
	// 					onClick={ handleContinuePaidAdsSetupClick }
	// 					eventName="gla_onboarding_open_paid_ads_setup_button_click"
	// 				/>
	// 			}
	// 		/>
	// 		{ showPaidAdsSetup && (
	// 			<PaidAdsSetupSections onStatesReceived={ setPaidAds } />
	// 		) }

	// 		<FaqsSection />

	// 		<StepContentFooter hidden={ ! showPaidAdsSetup }>
	// 			<Flex justify="right" gap={ 4 }>
	// 				{ createSkipButton(
	// 					__(
	// 						'Skip paid ads creation',
	// 						'google-listings-and-ads'
	// 					)
	// 				) }
	// 				<AppButton
	// 					isPrimary
	// 					data-action={ ACTION_COMPLETE }
	// 					text={ __(
	// 						'Complete setup',
	// 						'google-listings-and-ads'
	// 					) }
	// 					loading={ completing === ACTION_COMPLETE }
	// 					disabled={ disabledComplete }
	// 					onClick={ handleCompleteClick }
	// 					eventName="gla_onboarding_complete_with_paid_ads_button_click"
	// 					eventProps={ {
	// 						budget: paidAds.amount,
	// 						audiences: paidAds.countryCodes?.join( ',' ),
	// 					} }
	// 				/>
	// 			</Flex>
	// 		</StepContentFooter>
	// 	</StepContent>
	// );
}
