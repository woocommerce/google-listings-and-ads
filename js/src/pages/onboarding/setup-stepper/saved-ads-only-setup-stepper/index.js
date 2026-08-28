/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Stepper } from '@woocommerce/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useAdminUrl from '~/hooks/useAdminUrl';
import useCompleteAdsSetup from '~/hooks/useCompleteAdsSetup';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import useTargetAudienceWithSuggestions from '../useTargetAudienceWithSuggestions';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import SetupAccounts from './setup-accounts';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AssetGroup from '~/components/paid-ads/asset-group';
import SetupPaidAds from './setup-paid-ads';
import convertToAssetGroupUpdateBody from '~/components/paid-ads/convertToAssetGroupUpdateBody';
import EuPoliticalDeclarationProvider from '~/components/eu-political-declaration/eu-political-declaration-provider';
import { GUIDE_NAMES } from '~/constants';
import { ADS_ONLY_STEP_NAME_KEY_MAP } from '../constants';
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
	const adminUrl = useAdminUrl();
	const [ step, setStep ] = useState( savedStep );
	const [ paidAdValues, setPaidAdValues ] = useState( {} );
	const { createNotice } = useDispatchCoreNotices();
	const { completeAdsSetup } = useCompleteAdsSetup();
	const { data: suggestedAudience } = useTargetAudienceWithSuggestions();
	const { data: countryCodes, targetAudience } =
		useTargetAudienceFinalCountryCodes();
	const {
		saveTargetAudience,
		createAdsWithAssetsCampaign,
		completeOnboarding,
	} = useAppDispatch();

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

	const redirectToDashboard = () => {
		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getDashboardUrl( query );
	};

	/**
	 * Handles the submission of the optimize campaign step.
	 */
	const handleSubmit = async ( values, enhancer ) => {
		try {
			const { dailyBudget, hasConfirmedEuPoliticalContent } =
				paidAdValues;

			const assets = convertToAssetGroupUpdateBody(
				{
					final_url: '',
					display_url_path: [ '', '' ],
					assets: {},
				},
				values
			);

			await createAdsWithAssetsCampaign(
				dailyBudget,
				countryCodes,
				assets,
				hasConfirmedEuPoliticalContent
			);

			// Complete onboarding after creating the campaign.
			await completeAdsSetup();
			await completeOnboarding();
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

		redirectToDashboard();
	};

	const handleSetupPaidAdsSubmit = ( values ) => {
		setPaidAdValues( values );
		setStep( ADS_ONLY_STEP_NAME_KEY_MAP.optimize_campaign );
	};

	const handleSetupPaidAdsSkipped = async () => {
		await completeAdsSetup();
		await completeOnboarding();

		redirectToDashboard();
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
					label: __( 'Set your budget', 'google-listings-and-ads' ),
					content: (
						<EuPoliticalDeclarationProvider
							context={ CONTEXT_ADS_ONLY_ONBOARDING }
						>
							<SetupPaidAds
								onSkip={ handleSetupPaidAdsSkipped }
								onSubmit={ handleSetupPaidAdsSubmit }
							/>
						</EuPoliticalDeclarationProvider>
					),
					onClick: handleStepClick,
				},
				{
					key: ADS_ONLY_STEP_NAME_KEY_MAP.optimize_campaign,
					label: __(
						'Create your campaign',
						'google-listings-and-ads'
					),
					content: (
						<CampaignAssetsForm
							countryCodes={ countryCodes }
							onSubmit={ handleSubmit }
						>
							<AssetGroup
								context={ CONTEXT_ADS_ONLY_ONBOARDING }
								onSkipClick={ handleSetupPaidAdsSkipped }
							/>
						</CampaignAssetsForm>
					),
				},
			] }
		/>
	);
};

export default SavedAdsOnlySetupStepper;
