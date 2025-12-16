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
import SetupServiceBasedAccounts from '../setup-service-based-accounts';
import OptimizeCampaign from './optimize-campaign';
import SetupPaidAds from '../setup-paid-ads';
import convertToAssetGroupUpdateBody from '~/components/paid-ads/convertToAssetGroupUpdateBody';
import { GUIDE_NAMES } from '~/constants';
import { ACTION_SUBMIT_CAMPAIGN_AND_ASSETS } from '~/components/paid-ads/asset-group';
import { SERVICE_BASED_STEP_NAME_KEY_MAP } from '../constants';
import { API_NAMESPACE } from '~/data/constants';
import { getDashboardUrl } from '~/utils/urls';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
	FILTER_ONBOARDING,
	CONTEXT_SERVICE_BASED_ONBOARDING,
} from '~/utils/tracks';

/**
 * Renders the stepper for service-based merchants.
 *
 * @param {Object} props React props
 * @param {string} [props.savedStep] A saved step overriding the current step
 * @fires gla_setup_mc with `{ triggered_by: 'step1-continue-button' | 'step2-continue-button', action: 'go-to-step2' | 'go-to-step3' }`.
 * @fires gla_setup_mc with `{ triggered_by: 'stepper-step1-button' | 'stepper-step2-button', action: 'go-to-step1' | 'go-to-step2' }`.
 */
const SavedServiceBasedSetupStepper = ( { savedStep } ) => {
	const createdCampaignIdRef = useRef( null );
	const adminUrl = useAdminUrl();
	const [ step, setStep ] = useState( savedStep );
	const { createNotice } = useDispatchCoreNotices();
	const { data: suggestedAudience } = useTargetAudienceWithSuggestions();
	const { targetAudience } = useTargetAudienceFinalCountryCodes();
	const { saveTargetAudience, updateCampaignAssetGroup } = useAppDispatch();

	useEventPropertiesFilter( FILTER_ONBOARDING, {
		context: CONTEXT_SERVICE_BASED_ONBOARDING,
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

		recordStepContinueEvent( 'gla_setup_merchant_based', from, to );
		setStep( to );
	};

	const handleSetupAccountsContinue = () => {
		continueStep( SERVICE_BASED_STEP_NAME_KEY_MAP.create_campaign );
	};

	const handleStepClick = ( stepKey ) => {
		// Only allow going back to the previous steps.
		if ( Number( stepKey ) < Number( step ) ) {
			recordStepperChangeEvent( 'gla_setup_merchant_based', stepKey );
			setStep( stepKey );
		}
	};

	const handleSetupPaidAdsComplete = ( payload ) => {
		createdCampaignIdRef.current = payload.createdCampaign.id;
		setStep( SERVICE_BASED_STEP_NAME_KEY_MAP.optimize_campaign );
	};

	const handleSetupPaidAdsSkipped = () => {
		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getDashboardUrl( query );
	};

	/**
	 * Handles the submission of the optimize campaign step.
	 */
	const handleSubmit = async ( values, enhancer ) => {
		const { action } = enhancer.submitter.dataset;

		try {
			if ( action === ACTION_SUBMIT_CAMPAIGN_AND_ASSETS ) {
				const id = createdCampaignIdRef.current;
				const path = `${ API_NAMESPACE }/ads/campaigns/asset-groups?campaign_id=${ id }`;

				const [ assetEntityGroup ] = await apiFetch( { path } );

				const body = convertToAssetGroupUpdateBody(
					assetEntityGroup,
					values
				);

				await updateCampaignAssetGroup( assetEntityGroup.id, body );
			}

			createNotice(
				'success',
				__(
					'You’ve successfully created a campaign!',
					'google-listings-and-ads'
				)
			);
		} catch ( e ) {
			enhancer.signalFailedSubmission();
			return;
		}

		// Add a query param `campaign=saved` to the dashboard URL to indicate that the campaign was successfully created and saved.
		getHistory().push( getDashboardUrl( { campaign: 'saved' } ) );
	};

	return (
		<Stepper
			className="gla-setup-stepper"
			currentStep={ step }
			steps={ [
				{
					key: SERVICE_BASED_STEP_NAME_KEY_MAP.accounts,
					label: __(
						'Set up your accounts',
						'google-listings-and-ads'
					),
					content: (
						<SetupServiceBasedAccounts
							onContinue={ handleSetupAccountsContinue }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: SERVICE_BASED_STEP_NAME_KEY_MAP.create_campaign,
					label: __( 'Create a campaign', 'google-listings-and-ads' ),
					content: (
						<SetupPaidAds
							redirectToProductFeed={ false }
							completeSetupButtonLabel={ __(
								'Continue',
								'google-listings-and-ads'
							) }
							onSetupComplete={ handleSetupPaidAdsComplete }
							onSetupSkipped={ handleSetupPaidAdsSkipped }
						/>
					),
					onClick: handleStepClick,
				},
				{
					key: SERVICE_BASED_STEP_NAME_KEY_MAP.optimize_campaign,
					label: __(
						'Optimize your campaign',
						'google-listings-and-ads'
					),
					content: <OptimizeCampaign onSubmit={ handleSubmit } />,
				},
			] }
		/>
	);
};

export default SavedServiceBasedSetupStepper;
