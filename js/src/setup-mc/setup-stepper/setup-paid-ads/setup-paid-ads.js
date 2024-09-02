/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { select } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';
import { noop } from 'lodash';

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
import PaidAdsFeaturesSection from './paid-ads-features-section';
import PaidAdsSetupSections from './paid-ads-setup-sections';
import { getProductFeedUrl } from '.~/utils/urls';
import { API_NAMESPACE, STORE_KEY } from '.~/data/constants';
import { GUIDE_NAMES } from '.~/constants';

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
 * - the final status has not yet been resolved when recording this event
 * - the status is not available, for example, the billing status is unknown if Google Ads account is not yet connected
 *
 * @event gla_onboarding_complete_button_click
 * @property {string} google_ads_account_status The connection status of merchant's Google Ads addcount, e.g. 'connected', 'disconnected', 'incomplete'
 * @property {string} billing_method_status The status of billing method of merchant's Google Ads addcount e.g. 'unknown', 'pending', 'approved', 'cancelled'
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
	const [ paidAds, setPaidAds ] = useState( {} );
	const [ completing, setCompleting ] = useState( null );

	const finishOnboardingSetup = async ( event, onBeforeFinish = noop ) => {
		setCompleting( event.target.dataset.action );

		try {
			await onBeforeFinish();
			await apiFetch( {
				path: `${ API_NAMESPACE }/mc/settings/sync`,
				method: 'POST',
			} );
		} catch ( e ) {
			setCompleting( null );

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

	const handleCompleteClick = async ( event ) => {
		const onBeforeFinish = handleSetupComplete.bind(
			null,
			paidAds.amount,
			paidAds.countryCodes
		);
		await finishOnboardingSetup( event, onBeforeFinish );
	};

	// The status check of Google Ads account connection is included in `paidAds.isReady`,
	// because when there is no connected account, it will disable the budget section and set the `amount` to `undefined`.
	const disabledComplete = completing === ACTION_SKIP || ! paidAds.isReady;

	function createSkipButton( text ) {
		const selector = select( STORE_KEY );
		const billing = selector.getGoogleAdsAccountBillingStatus();

		const eventProps = {
			google_ads_account_status: googleAdsAccount?.status,
			billing_method_status: billing?.status ?? 'unknown',
			campaign_form_validation: paidAds.isValid ? 'valid' : 'invalid',
		};

		const disabledSkip =
			completing === ACTION_COMPLETE || ! hasGoogleAdsConnection;

		return (
			<AppButton
				isTertiary
				data-action={ ACTION_SKIP }
				text={ text }
				loading={ completing === ACTION_SKIP }
				disabled={ disabledSkip }
				onClick={ finishOnboardingSetup }
				eventName="gla_onboarding_complete_button_click"
				eventProps={ eventProps }
			/>
		);
	}

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Create a campaign to advertise your products',
					'google-listings-and-ads'
				) }
				description={ __(
					'You’re ready to set up a Performance Max campaign to drive more sales with ads. Your products will be included in the campaign after they’re approved.',
					'google-listings-and-ads'
				) }
			/>
			<PaidAdsFeaturesSection
				hideBudgetContent={ ! hasGoogleAdsConnection }
			/>
			<PaidAdsSetupSections onStatesReceived={ setPaidAds } />
			<FaqsSection />
			<StepContentFooter>
				<Flex justify="right" gap={ 4 }>
					{ createSkipButton(
						__(
							'Skip paid ads creation',
							'google-listings-and-ads'
						)
					) }
					<AppButton
						isPrimary
						data-action={ ACTION_COMPLETE }
						text={ __(
							'Complete setup',
							'google-listings-and-ads'
						) }
						loading={ completing === ACTION_COMPLETE }
						disabled={ disabledComplete }
						onClick={ handleCompleteClick }
						eventName="gla_onboarding_complete_with_paid_ads_button_click"
						eventProps={ {
							budget: paidAds.amount,
							audiences: paidAds.countryCodes?.join( ',' ),
						} }
					/>
				</Flex>
			</StepContentFooter>
		</StepContent>
	);
}
