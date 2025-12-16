/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useAdminUrl from '~/hooks/useAdminUrl';
import BudgetIncentivePrompt from '~/components/paid-ads/budget-incentive-prompt';
import useAdsSetupCompleteCallback from '~/hooks/useAdsSetupCompleteCallback';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import AppButton from '~/components/app-button';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import SkipButton from '../skip-button';
import clientSession from '../clientSession';
import { ACTION_SKIP } from '../constants';
import { GUIDE_NAMES, GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { getDashboardUrl } from '~/utils/urls';
import { CONTEXT_SERVICE_BASED_ONBOARDING } from '~/utils/tracks';

/**
 * Renders the create campaign step for service based onboarding setup stepper.
 * @param {Object}   props			  Component props.
 * @param {Function} props.onContinue Callback fired when the continue button is clicked.
 */
const CreateCampaign = ( { onContinue } ) => {
	const adminUrl = useAdminUrl();
	const budgetPromptRef = useRef();
	const [ handleSetupComplete, isSubmitting ] = useAdsSetupCompleteCallback();
	const [ completing, setCompleting ] = useState( null );
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const handleSkipCreatePaidAds = async () => {
		setCompleting( ACTION_SKIP );

		// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getDashboardUrl( query );
	};

	const createSkipButton = ( formContext ) => {
		const { isValidForm } = formContext;

		return (
			<SkipButton
				isValidForm={ isValidForm }
				onSkipCreatePaidAds={ handleSkipCreatePaidAds }
				loading={ completing === ACTION_SKIP }
				disabled={ isSubmitting }
			/>
		);
	};

	const createContinueButton = ( formContext ) => {
		const { isValidForm, values, enhancer, adapter } = formContext;
		const disabled =
			completing === ACTION_SKIP || ! isValidForm || ! isBillingCompleted;

		const handleClick = () => {
			budgetPromptRef.current
				.resolve( values.dailyBudget )
				.then( async ( amount ) => {
					if ( amount === null ) {
						const { hasConfirmedEuPoliticalContent, level } =
							values;
						const { budgetRecommendation } = adapter;

						try {
							let dailyBudget = amount;

							if ( level !== 'custom' ) {
								dailyBudget =
									budgetRecommendation?.[ level ]
										?.dailyBudget;
							}

							handleSetupComplete(
								dailyBudget,
								countryCodes,
								hasConfirmedEuPoliticalContent,
								( response ) => {
									onContinue( response?.createdCampaign?.id );
								}
							);
						} catch ( error ) {
							enhancer.signalFailedSubmission();
						}
					} else if ( Number.isFinite( amount ) ) {
						formContext.setValues( {
							level: 'custom',
							amount,
						} );
					}
				} );
		};

		return (
			<AppButton
				isPrimary
				disabled={ disabled }
				onClick={ handleClick }
				loading={ isSubmitting }
				text={ __( 'Continue', 'google-listings-and-ads' ) }
			/>
		);
	};

	const paidAds = {
		...clientSession.getCampaign(),
	};
	return (
		<CampaignAssetsForm
			countryCodes={ countryCodes }
			initialCampaign={ paidAds }
			onChange={ ( _, values ) => {
				clientSession.setCampaign( values );
			} }
		>
			<AdsCampaign
				headerTitle={ __(
					'Create your campaign',
					'google-listings-and-ads'
				) }
				context={ CONTEXT_SERVICE_BASED_ONBOARDING }
				skipButton={ createSkipButton }
				continueButton={ createContinueButton }
			/>
			<BudgetIncentivePrompt
				ref={ budgetPromptRef }
				countryCodes={ countryCodes }
			/>
		</CampaignAssetsForm>
	);
};

export default CreateCampaign;
