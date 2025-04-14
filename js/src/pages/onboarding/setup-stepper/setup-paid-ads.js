/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useAdminUrl from '~/hooks/useAdminUrl';
import useAdsSetupCompleteCallback from '~/hooks/useAdsSetupCompleteCallback';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AppButton from '~/components/app-button';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import { getProductFeedUrl } from '~/utils/urls';
import { handleApiError } from '~/utils/handleError';
import { useAppDispatch } from '~/data';
import { GUIDE_NAMES, GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { ACTION_COMPLETE, ACTION_SKIP } from './constants';
import SkipButton from './skip-button';
import clientSession from './clientSession';
import useBudgetRecommendation from '~/hooks/useBudgetRecommendation';
import AppSpinner from '~/components/app-spinner';

/**
 * Clicking on the "Complete setup" button to complete the onboarding flow with paid ads.
 *
 * @event gla_onboarding_complete_with_paid_ads_button_click
 * @property {number} budget The budget for the campaign
 * @property {string} audiences The targeted audiences for the campaign
 */

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 * @fires gla_onboarding_complete_with_paid_ads_button_click
 */
export default function SetupPaidAds() {
	const adminUrl = useAdminUrl();
	const [ completing, setCompleting ] = useState( null );
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const { recommendedDailyBudget, hasFinishedResolution } =
		useBudgetRecommendation( countryCodes );
	const [ handleSetupComplete ] = useAdsSetupCompleteCallback();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { syncSettings } = useAppDispatch();

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const finishOnboardingSetup = async ( onBeforeFinish = noop ) => {
		try {
			await syncSettings();
			await onBeforeFinish();
		} catch ( e ) {
			setCompleting( null );

			handleApiError(
				e,
				__(
					'Unable to complete your setup.',
					'google-listings-and-ads'
				)
			);
			return;
		}

		// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getProductFeedUrl( query );
	};

	const handleSkipCreatePaidAds = async () => {
		setCompleting( ACTION_SKIP );
		await finishOnboardingSetup();
	};

	const createSkipButton = ( formContext ) => {
		const { isValidForm } = formContext;

		return (
			<SkipButton
				isValidForm={ isValidForm }
				onSkipCreatePaidAds={ handleSkipCreatePaidAds }
				disabled={ completing === ACTION_COMPLETE }
				loading={ completing === ACTION_SKIP }
			/>
		);
	};

	const createContinueButton = ( formContext ) => {
		const { isValidForm, values } = formContext;
		const { amount } = values;

		const disabled =
			completing === ACTION_SKIP || ! isValidForm || ! isBillingCompleted;

		const handleCompleteClick = async () => {
			setCompleting( ACTION_COMPLETE );
			const onBeforeFinish = handleSetupComplete.bind(
				null,
				amount,
				countryCodes
			);

			await finishOnboardingSetup( onBeforeFinish );
		};

		return (
			<AppButton
				isPrimary
				disabled={ disabled }
				onClick={ handleCompleteClick }
				loading={ completing === ACTION_COMPLETE }
				text={ __( 'Complete setup', 'google-listings-and-ads' ) }
				eventName="gla_onboarding_complete_with_paid_ads_button_click"
				eventProps={ {
					budget: amount,
					audiences: countryCodes?.join( ',' ),
				} }
			/>
		);
	};

	const paidAds = {
		amount: recommendedDailyBudget,
		...clientSession.getCampaign(),
	};

	if ( ! hasFinishedResolution || ! countryCodes ) {
		return <AppSpinner />;
	}

	return (
		<CampaignAssetsForm
			initialCampaign={ paidAds }
			recommendedDailyBudget={ recommendedDailyBudget }
			onChange={ ( _, values ) => {
				if ( values.amount >= recommendedDailyBudget ) {
					clientSession.setCampaign( values );
				}
			} }
		>
			<AdsCampaign
				headerTitle={ __(
					'Create a campaign to advertise your products',
					'google-listings-and-ads'
				) }
				continueButton={ createContinueButton }
				skipButton={ createSkipButton }
				context="setup-mc"
			/>
		</CampaignAssetsForm>
	);
}
