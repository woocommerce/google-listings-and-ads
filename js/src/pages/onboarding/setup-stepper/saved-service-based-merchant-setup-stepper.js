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
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import useTargetAudienceWithSuggestions from './useTargetAudienceWithSuggestions';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import SetupServiceBasedAccounts from './setup-service-based-accounts';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import ContinueButton from '~/components/paid-ads/continue-button';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import convertToAssetGroupUpdateBody from '~/components/paid-ads/convertToAssetGroupUpdateBody';
import AssetGroup, {
	ACTION_SUBMIT_CAMPAIGN_AND_ASSETS,
} from '~/components/paid-ads/asset-group';
import { SERVICE_BASED_STEP_NAME_KEY_MAP } from './constants';
import { API_NAMESPACE } from '~/data/constants';
import { getDashboardUrl } from '~/utils/urls';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
	FILTER_ONBOARDING,
	CONTEXT_SERVICE_BASED_ONBOARDING,
} from '~/utils/tracks';

/**
 * @param {Object} props React props
 * @param {string} [props.savedStep] A saved step overriding the current step
 * @fires gla_setup_mc with `{ triggered_by: 'step1-continue-button' | 'step2-continue-button', action: 'go-to-step2' | 'go-to-step3' }`.
 * @fires gla_setup_mc with `{ triggered_by: 'stepper-step1-button' | 'stepper-step2-button', action: 'go-to-step1' | 'go-to-step2' }`.
 */
const SavedServiceBasedMerchantSetupStepper = ( { savedStep } ) => {
	const createdCampaignIdRef = useRef( null );
	const [ step, setStep ] = useState( savedStep );
	const [ selectedDailyBudget, setSelectedDailyBudget ] = useState( null );
	const { createNotice } = useDispatchCoreNotices();
	const { data: suggestedAudience } = useTargetAudienceWithSuggestions();
	const { data: countryCodes, targetAudience } =
		useTargetAudienceFinalCountryCodes();
	const { saveTargetAudience, createAdsCampaign, updateCampaignAssetGroup } =
		useAppDispatch();

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

	const handleOnCreateCampaignContinueClick = ( formContext ) => {
		const level = formContext.values.level;
		let userDailyBudget = formContext.values.amount;

		if ( level !== 'custom' ) {
			userDailyBudget =
				formContext.adapter.budgetRecommendation[ level ].dailyBudget;
		}

		setSelectedDailyBudget( userDailyBudget );

		setStep( SERVICE_BASED_STEP_NAME_KEY_MAP.optimize_campaign );
	};

	const handleSubmit = async ( values, enhancer ) => {
		const { action } = enhancer.submitter.dataset;

		try {
			// Avoid re-creating a new campaign if the subsequent asset group update is failed.
			if ( createdCampaignIdRef.current === null ) {
				const { hasConfirmedEuPoliticalContent } = values;
				const payload = await createAdsCampaign(
					selectedDailyBudget,
					countryCodes,
					hasConfirmedEuPoliticalContent
				);
				createdCampaignIdRef.current = payload.createdCampaign.id;
			}

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
						<CampaignAssetsForm countryCodes={ countryCodes }>
							<AdsCampaign
								headerTitle={ __(
									'Create your campaign',
									'google-listings-and-ads'
								) }
								context={ CONTEXT_SERVICE_BASED_ONBOARDING }
								continueButton={ ( formContext ) => (
									<ContinueButton
										formProps={ formContext }
										onClick={ () => {
											handleOnCreateCampaignContinueClick(
												formContext
											);
										} }
									/>
								) }
							/>
						</CampaignAssetsForm>
					),
					onClick: handleStepClick,
				},
				{
					key: SERVICE_BASED_STEP_NAME_KEY_MAP.optimize_campaign,
					label: __(
						'Optimize your campaign',
						'google-listings-and-ads'
					),
					content: (
						<CampaignAssetsForm
							onSubmit={ handleSubmit }
							countryCodes={ countryCodes }
						>
							<AssetGroup />
						</CampaignAssetsForm>
					),
				},
			] }
		/>
	);
};

export default SavedServiceBasedMerchantSetupStepper;
