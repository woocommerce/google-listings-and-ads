/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Stepper } from '@woocommerce/components';
import { getHistory } from '@woocommerce/navigation';
import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useAdminUrl from '~/hooks/useAdminUrl';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import useTargetAudienceWithSuggestions from '../useTargetAudienceWithSuggestions';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import SetupAccounts from './setup-accounts';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AssetGroup from '~/components/paid-ads/asset-group';
import SetupPaidAds from './setup-paid-ads';
import convertToAssetGroupUpdateBody from '~/components/paid-ads/convertToAssetGroupUpdateBody';
import { GUIDE_NAMES } from '~/constants';
import { ADS_ONLY_STEP_NAME_KEY_MAP } from '../constants';
import { API_NAMESPACE } from '~/data/constants';
import { getDashboardUrl } from '~/utils/urls';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
	FILTER_ONBOARDING,
	CONTEXT_ADS_ONLY_ONBOARDING,
} from '~/utils/tracks';

/**
 * Renders an Ads only stepper which is for service based merchants who only need to set up ads.
 *
 * @param {Object} props React props
 * @param {string} [props.savedStep] A saved step overriding the current step
 * @fires gla_setup_ads_only with `{ triggered_by: 'step1-continue-button' | 'step2-continue-button', action: 'go-to-step2' | 'go-to-step3' }`.
 * @fires gla_setup_ads_only with `{ triggered_by: 'stepper-step1-button' | 'stepper-step2-button', action: 'go-to-step1' | 'go-to-step2' }`.
 */
const SavedAdsOnlySetupStepper = ( { savedStep } ) => {
	const createdCampaignRef = useRef( null );
	const adminUrl = useAdminUrl();
	const [ step, setStep ] = useState( savedStep );
	const { createNotice } = useDispatchCoreNotices();
	const { data: suggestedAudience } = useTargetAudienceWithSuggestions();
	const { data: countryCodes, targetAudience } =
		useTargetAudienceFinalCountryCodes();
	const { saveTargetAudience, updateCampaignAssetGroup, completeOnboarding } =
		useAppDispatch();

	useEventPropertiesFilter( FILTER_ONBOARDING, {
		context: CONTEXT_ADS_ONLY_ONBOARDING,
		step,
	} );

	// Auto-save the suggested audience data as the initial values to fall back with the original implementation.
	// Ref: https://github.com/woocommerce/google-listings-and-ads/blob/2.0.2/js/src/setup-mc/setup-stepper/choose-audience/form-content.js#L37
	useEffect( () => {
		if (
			targetAudience?.location === null &&
			suggestedAudience?.location
		) {
			saveTargetAudience( suggestedAudience );
		}
	}, [ targetAudience, suggestedAudience, saveTargetAudience ] );

	/**
	 * Handles "onContinue" callback to set the current step and record event tracking.
	 *
	 * @param {string} to The next step to go to.
	 */
	const continueStep = ( to ) => {
		const from = step;

		recordStepContinueEvent( 'gla_setup_ads_only', from, to );
		setStep( to );
	};

	const handleSetupAccountsContinue = () => {
		continueStep( ADS_ONLY_STEP_NAME_KEY_MAP.create_campaign );
	};

	const handleStepClick = ( stepKey ) => {
		// Only allow going back to the previous steps.
		if ( Number( stepKey ) < Number( step ) ) {
			recordStepperChangeEvent( 'gla_setup_ads_only', stepKey );
			setStep( stepKey );
		}
	};

	/**
	 * Handles the submission of the optimize campaign step.
	 */
	const handleSubmit = async ( values, enhancer ) => {
		try {
			const { id } = createdCampaignRef.current;
			if ( ! id ) {
				return;
			}

			const path = `${ API_NAMESPACE }/ads/campaigns/asset-groups?campaign_id=${ id }`;
			const [ assetEntityGroup ] = await apiFetch( { path } );
			const body = convertToAssetGroupUpdateBody(
				assetEntityGroup,
				values
			);

			await updateCampaignAssetGroup( assetEntityGroup.id, body );

			// Complete onboarding after saving the asset group successfully.
			await completeOnboarding();
			createNotice(
				'success',
				__(
					'You’ve successfully created a campaign!',
					'google-listings-and-ads'
				)
			);

			// Redirect to dashboard with success guide after completion.
			const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
			window.location.href = adminUrl + getDashboardUrl( query );
		} catch ( e ) {
			enhancer.signalFailedSubmission();
			return;
		}

		// Add a query param `campaign=saved` to the dashboard URL to indicate that the campaign was successfully created and saved.
		getHistory().push( getDashboardUrl( { campaign: 'saved' } ) );
	};

	const handleSetupPaidAdsSubmit = ( payload ) => {
		createdCampaignRef.current = payload.createdCampaign;
		setStep( ADS_ONLY_STEP_NAME_KEY_MAP.optimize_campaign );
	};

	const finishOnboardingSetup = async () => {
		await completeOnboarding();
	};

	const handleSetupPaidAdsSkipped = async () => {
		await finishOnboardingSetup();

		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getDashboardUrl( query );
	};

	return (
		<Stepper
			className="gla-setup-stepper"
			currentStep={ step }
			steps={ [
				{
					key: ADS_ONLY_STEP_NAME_KEY_MAP.accounts,
					label: __(
						'Set up your accounts',
						'google-listings-and-ads'
					),
					content: (
						<SetupAccounts
							onContinue={ handleSetupAccountsContinue }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: ADS_ONLY_STEP_NAME_KEY_MAP.create_campaign,
					label: __( 'Create a campaign', 'google-listings-and-ads' ),
					content: (
						<SetupPaidAds
							onSubmit={ handleSetupPaidAdsSubmit }
							onSkip={ handleSetupPaidAdsSkipped }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: ADS_ONLY_STEP_NAME_KEY_MAP.optimize_campaign,
					label: __(
						'Optimize your campaign',
						'google-listings-and-ads'
					),
					content: (
						<CampaignAssetsForm
							onSubmit={ handleSubmit }
							countryCodes={ countryCodes }
						>
							<AssetGroup
								context={ CONTEXT_ADS_ONLY_ONBOARDING }
								campaign={ createdCampaignRef.current }
							/>
						</CampaignAssetsForm>
					),
				},
			] }
		/>
	);
};

export default SavedAdsOnlySetupStepper;
