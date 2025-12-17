/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useAdsSetupCompleteCallback from '~/hooks/useAdsSetupCompleteCallback';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import BudgetIncentivePrompt from '~/components/paid-ads/budget-incentive-prompt';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AppButton from '~/components/app-button';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { handleApiError } from '~/utils/handleError';
import { FILTER_BUDGET_RECOMMENDATIONS, recordGlaEvent } from '~/utils/tracks';
import { useAppDispatch } from '~/data';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { ACTION_COMPLETE, ACTION_SKIP } from './constants';
import SkipButton from './skip-button';
import clientSession from './clientSession';
import AppSpinner from '~/components/app-spinner';

/**
 * Clicking on the "Complete setup" button to complete the onboarding flow with paid ads.
 *
 * @event gla_onboarding_complete_with_paid_ads_button_click
 * @property {string} level The selected level of the budget recommendation, e.g. 'low', 'recommended', 'high', 'custom'.
 * @property {number} budget The budget for the campaign
 * @property {string} audiences The targeted audiences for the campaign
 * @property {string} source The data source of the budget recommendations, e.g. 'google-ads-api', 'fallback-database'.
 * @property {number} recommended_budget The recommended daily budget displayed to merchants regardless of the final amount they choose.
 */

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 * @param {Object} props React props
 * @param {'setup-mc'|'setup-ads-only'} props.context A context indicating which page this component is used on.
 * @param {Function} [props.onSetupComplete] Callback fired when the setup is finished.
 * @param {Function} [props.onSetupSkipped] Callback fired when the setup is skipped.
 * @param {string}   [props.completeSetupButtonLabel] Label for the complete setup button.
 * @fires gla_onboarding_complete_with_paid_ads_button_click
 */
export default function SetupPaidAds( {
	context = 'setup-mc',
	onSetupComplete = noop,
	onSetupSkipped = noop,
	completeSetupButtonLabel = __(
		'Complete setup',
		'google-listings-and-ads'
	),
} ) {
	const budgetPromptRef = useRef();
	const { isReady: isMCAccountReady } = useGoogleMCAccount();
	const [ completing, setCompleting ] = useState( null );
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const [ handleSetupComplete ] = useAdsSetupCompleteCallback();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { syncSettings } = useAppDispatch();
	const getEventProps = useEventPropertiesFilter(
		FILTER_BUDGET_RECOMMENDATIONS
	);

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const finishOnboardingSetup = async ( onBeforeFinish = noop ) => {
		try {
			// Sync settings only if there is a connected MC account.
			if ( isMCAccountReady ) {
				await syncSettings();
			}

			const response = await onBeforeFinish();

			if ( response ) {
				onSetupComplete( response );
				return;
			}

			onSetupSkipped();
		} catch ( e ) {
			setCompleting( null );

			handleApiError(
				e,
				__(
					'Unable to complete your setup.',
					'google-listings-and-ads'
				)
			);
		}
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
		const disabled =
			completing === ACTION_SKIP || ! isValidForm || ! isBillingCompleted;

		const handleClick = () => {
			budgetPromptRef.current
				.resolve( values.dailyBudget )
				.then( ( amount ) => {
					if ( amount === null ) {
						formContext.handleSubmit();
					} else if ( Number.isFinite( amount ) ) {
						formContext.setValues( { level: 'custom', amount } );
					}
				} );
		};

		return (
			<AppButton
				isPrimary
				disabled={ disabled }
				onClick={ handleClick }
				loading={ completing === ACTION_COMPLETE }
				text={ completeSetupButtonLabel }
			/>
		);
	};

	const paidAds = {
		...clientSession.getCampaign(),
	};

	if ( ! countryCodes ) {
		return <AppSpinner />;
	}

	const handleSubmit = async ( values ) => {
		const { level, dailyBudget, hasConfirmedEuPoliticalContent } = values;
		const onBeforeFinish = handleSetupComplete.bind(
			null,
			dailyBudget,
			countryCodes,
			hasConfirmedEuPoliticalContent
		);

		setCompleting( ACTION_COMPLETE );

		recordGlaEvent(
			'gla_onboarding_complete_with_paid_ads_button_click',
			getEventProps( {
				level,
				budget: dailyBudget,
				audiences: countryCodes.join( ',' ),
			} )
		);

		await finishOnboardingSetup( onBeforeFinish );
	};

	return (
		<CampaignAssetsForm
			initialCampaign={ paidAds }
			countryCodes={ countryCodes }
			onChange={ ( _, values ) => {
				clientSession.setCampaign( values );
			} }
			onSubmit={ handleSubmit }
		>
			<AdsCampaign
				headerTitle={ __(
					'Create a campaign to advertise your products',
					'google-listings-and-ads'
				) }
				continueButton={ createContinueButton }
				skipButton={ createSkipButton }
				context={ context }
			/>
			<BudgetIncentivePrompt
				ref={ budgetPromptRef }
				countryCodes={ countryCodes }
			/>
		</CampaignAssetsForm>
	);
}
