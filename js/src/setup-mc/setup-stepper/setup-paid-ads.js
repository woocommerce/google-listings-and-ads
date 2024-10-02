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
import { getProductFeedUrl } from '.~/utils/urls';
import { API_NAMESPACE } from '.~/data/constants';
import { GUIDE_NAMES } from '.~/constants';

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 */
export default function SetupPaidAds() {
	const adminUrl = useAdminUrl();
	const [ hasError, setHasError ] = useState( false );
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
			setHasError( true );

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

	const handleCompleteClick = async ( paidAdsData ) => {
		const onBeforeFinish = handleSetupComplete.bind(
			null,
			paidAdsData.amount,
			countryCodes
		);

		await finishOnboardingSetup( onBeforeFinish );
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
				onSkip={ handleSkipCreatePaidAds }
				onContinue={ handleCompleteClick }
				hasError={ hasError }
				isOnboardingFlow
			/>
		</CampaignAssetsForm>
	);
}
