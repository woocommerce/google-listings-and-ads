/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useAdminUrl from '.~/hooks/useAdminUrl';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useAdsSetupCompleteCallback from '.~/hooks/useAdsSetupCompleteCallback';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import AdsCampaign from '.~/components/paid-ads/ads-campaign';
import CampaignAssetsForm from '.~/components/paid-ads/campaign-assets-form';
import AppButton from '.~/components/app-button';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import { getProductFeedUrl } from '.~/utils/urls';
import { API_NAMESPACE } from '.~/data/constants';
import { GUIDE_NAMES, GOOGLE_ADS_BILLING_STATUS } from '.~/constants';
import { ACTION_COMPLETE, ACTION_SKIP } from './constants';
import validateCampaign from '.~/components/paid-ads/validateCampaign';
import SkipButton from './skip-button';
import clientSession from './clientSession';

/**
 * Clicking on the "Complete setup" button to complete the onboarding flow with paid ads.
 *
 * @event gla_onboarding_complete_with_paid_ads_button_click
 * @property {number} budget The budget for the campaign
 * @property {string} audiences The targeted audiences for the campaign
 */

/**
 *
 * @typedef {Object} PaidAdsData
 * @property {number|undefined} amount Daily average cost of the paid ads campaign.
 * @property {boolean} isValid Whether the campaign data are valid values.
 */

const defaultPaidAds = {
	amount: 0,
	isValid: false,
};

/**
 * Resolve the initial paid ads data from the given paid ads data.
 * Parts of the resolved data are used in the `initialCampaign` prop of `CampaignAssetsForm` component.
 *
 * @return {PaidAdsData} The resolved paid ads data.
 */
function resolveInitialPaidAds() {
	const startingPaidAds = {
		...defaultPaidAds,
		...clientSession.getCampaign(),
	};
	const nextPaidAds = { ...startingPaidAds };
	nextPaidAds.isValid = ! Object.keys( validateCampaign( nextPaidAds ) )
		.length;

	return nextPaidAds;
}

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 * @fires gla_onboarding_complete_with_paid_ads_button_click
 */
export default function SetupPaidAds() {
	const adminUrl = useAdminUrl();
	const [ completing, setCompleting ] = useState( null );
	const { createNotice } = useDispatchCoreNotices();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const [ handleSetupComplete ] = useAdsSetupCompleteCallback();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const paidAds = resolveInitialPaidAds();

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const finishOnboardingSetup = async ( onBeforeFinish = noop ) => {
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

	return (
		<CampaignAssetsForm
			initialCampaign={ paidAds }
			onChange={ ( _, values, isValid ) => {
				clientSession.setCampaign( { ...values, isValid } );
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
