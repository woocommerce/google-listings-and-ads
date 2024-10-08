/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';
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
import validateCampaign from '.~/components/paid-ads/validateCampaign';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import { getProductFeedUrl } from '.~/utils/urls';
import { API_NAMESPACE } from '.~/data/constants';
import { GUIDE_NAMES, GOOGLE_ADS_BILLING_STATUS } from '.~/constants';
import { ACTION_COMPLETE, ACTION_SKIP } from './constants';
import SkipButton from './skip-button';
import clientSession from './clientSession';

/**
 *
 * @typedef {Object} PaidAdsData
 * @property {number|undefined} amount Daily average cost of the paid ads campaign.
 * @property {boolean} isValid Whether the campaign data are valid values.
 * @property {boolean} isReady Whether the campaign data and the billing setting are ready for completing the paid ads setup.
 */

const defaultPaidAds = {
	amount: 0,
	isValid: false,
	isReady: false,
};

/**
 * Resolve the initial paid ads data from the given paid ads data.
 * Parts of the resolved data are used in the `initialValues` prop of `Form` component.
 *
 * @param {PaidAdsData} paidAds The paid ads data as the base to be resolved with other states.
 * @return {PaidAdsData} The resolved paid ads data.
 */
function resolveInitialPaidAds( paidAds ) {
	const nextPaidAds = { ...paidAds };
	nextPaidAds.isValid = ! Object.keys( validateCampaign( nextPaidAds ) )
		.length;

	return nextPaidAds;
}

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
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const [ paidAds, setPaidAds ] = useState( () => {
		// Resolve the starting paid ads data with the campaign data stored in the client session.
		const startingPaidAds = {
			...defaultPaidAds,
			...clientSession.getCampaign(),
		};

		return resolveInitialPaidAds( startingPaidAds );
	} );

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
		await finishOnboardingSetup();
	};

	const CreateSkipButton = () => {
		return (
			<SkipButton
				paidAds={ paidAds }
				onSkipCreatePaidAds={ handleSkipCreatePaidAds }
				disabled={ completing === ACTION_COMPLETE }
				loading={ completing === ACTION_SKIP }
			/>
		);
	};

	const ContinueButton = ( formContext ) => {
		const { isValidForm } = formContext;

		const disabled =
			completing === ACTION_SKIP ||
			! paidAds.isValid ||
			! isValidForm ||
			! isBillingCompleted;

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

	useEffect( () => {
		clientSession.setCampaign( paidAds );
	}, [ paidAds ] );

	return (
		<CampaignAssetsForm
			initialCampaign={ paidAds }
			validate={ validateCampaign }
			onChange={ ( _, values, isValid ) => {
				setPaidAds( { ...paidAds, ...values, isValid } );
			} }
		>
			<AdsCampaign
				headerTitle={ __(
					'Create a campaign to advertise your products',
					'google-listings-and-ads'
				) }
				continueButton={ ContinueButton }
				skipButton={ CreateSkipButton }
				isOnboardingFlow
			/>
		</CampaignAssetsForm>
	);
}
