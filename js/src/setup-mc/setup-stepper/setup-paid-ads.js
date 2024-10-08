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
import { getProductFeedUrl } from '.~/utils/urls';
import { API_NAMESPACE } from '.~/data/constants';
import { GUIDE_NAMES } from '.~/constants';
import { ACTION_COMPLETE, ACTION_SKIP } from './constants';
import SkipButton from './skip-button';

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 */
export default function SetupPaidAds() {
	const adminUrl = useAdminUrl();
	const [ completing, setCompleting ] = useState( null );
	const { createNotice } = useDispatchCoreNotices();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const [ handleSetupComplete ] = useAdsSetupCompleteCallback();

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
		await finishOnboardingSetup();
	};

	const skipButton = ( paidAds ) => {
		return (
			<SkipButton
				paidAds={ paidAds }
				onSkipCreatePaidAds={ handleSkipCreatePaidAds }
				disabled={ completing === ACTION_COMPLETE }
				loading={ completing === ACTION_SKIP }
			/>
		);
	};

	const continueButton = ( formContext, paidAds ) => {
		const { isValidForm } = formContext;
		const disabled =
			completing === ACTION_SKIP || ! paidAds.isReady || ! isValidForm;

		const handleCompleteClick = async () => {
			setCompleting( ACTION_COMPLETE );
			const onBeforeFinish = handleSetupComplete.bind(
				null,
				paidAds.amount,
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
				data-action="ACTION_COMPLETE"
				text={ __( 'Complete setup', 'google-listings-and-ads' ) }
				eventName="gla_onboarding_complete_with_paid_ads_button_click"
				eventProps={ {
					budget: paidAds.amount,
					audiences: countryCodes?.join( ',' ),
				} }
			/>
		);
	};

	return (
		<CampaignAssetsForm
			initialCampaign={ {
				amount: 0,
			} }
		>
			<AdsCampaign
				headerTitle={ __(
					'Create a campaign to advertise your products',
					'google-listings-and-ads'
				) }
				continueButton={ continueButton }
				skipButton={ skipButton }
				isOnboardingFlow
			/>
		</CampaignAssetsForm>
	);
}
