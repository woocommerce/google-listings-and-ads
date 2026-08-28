/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useAdminUrl from '~/hooks/useAdminUrl';
import useAdsSetupCompleteCallback from '~/hooks/useAdsSetupCompleteCallback';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import BudgetIncentivePrompt from '~/components/paid-ads/budget-incentive-prompt';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AppButton from '~/components/app-button';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import { getProductFeedUrl } from '~/utils/urls';
import { handleApiError } from '~/utils/handleError';
import {
	FILTER_BUDGET_RECOMMENDATIONS,
	CONTEXT_EXTENSION_ONBOARDING,
	recordGlaEvent,
} from '~/utils/tracks';
import { useAppDispatch } from '~/data';
import {
	GUIDE_NAMES,
	GOOGLE_ADS_BILLING_STATUS,
	EU_POLITICAL_ADVERTISING_DECLARATION_REQUIRED_ERROR_CODE,
} from '~/constants';
import { ACTION_COMPLETE, ACTION_SKIP } from './constants';
import SkipButton from './skip-button';
import clientSession from './clientSession';
import AppSpinner from '~/components/app-spinner';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';
import useApplyCYOIncentive from '~/hooks/useApplyCYOIncentive';

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
 * Selecting a "Choose Your Own" incentive offer when setting up paid ads during onboarding.
 *
 * @event gla_onboarding_with_cyo_incentive_selected
 * @property {string} context The context in which the incentive offer is selected, e.g. 'create-ads', 'edit-ads', 'setup-ads', 'setup-mc', or 'setup-ads-only'.
 * @property {string} level The level of the selected incentive offer, e.g. 'low', 'medium', or 'high'.
 */

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 * @fires gla_onboarding_complete_with_paid_ads_button_click
 * @fires gla_onboarding_with_cyo_incentive_selected
 */
export default function SetupPaidAds() {
	const budgetPromptRef = useRef();
	const adminUrl = useAdminUrl();
	const [ completing, setCompleting ] = useState( null );
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const [ handleSetupComplete ] = useAdsSetupCompleteCallback();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { syncSettings } = useAppDispatch();
	const { handleError: handleEuPoliticalDeclarationError } =
		useEuPoliticalDeclarationContext();
	const { applyIncentive, loading: incentiveLoading } =
		useApplyCYOIncentive();
	const getEventProps = useEventPropertiesFilter(
		FILTER_BUDGET_RECOMMENDATIONS
	);

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const finishOnboardingSetup = async ( onBeforeFinish = noop ) => {
		try {
			await syncSettings();
			await onBeforeFinish();
		} catch ( e ) {
			handleEuPoliticalDeclarationError( e );
			setCompleting( null );

			if (
				e.code !==
				EU_POLITICAL_ADVERTISING_DECLARATION_REQUIRED_ERROR_CODE
			) {
				handleApiError(
					e,
					__(
						'Unable to complete your setup.',
						'google-listings-and-ads'
					)
				);
			}
			return;
		}

		// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getProductFeedUrl( query );
	};

	const skipCreatePaidAds = async ( incentiveOffer ) => {
		setCompleting( ACTION_SKIP );

		const applied = await applyIncentive( incentiveOffer );

		if ( applied ) {
			recordGlaEvent( 'gla_onboarding_with_cyo_incentive_selected', {
				context: CONTEXT_EXTENSION_ONBOARDING,
				level: incentiveOffer,
			} );
		}

		await finishOnboardingSetup();
	};

	const createSkipButton = ( formContext ) => {
		const { isValidForm, values } = formContext;

		const handleSkipCreatePaidAds = () => {
			skipCreatePaidAds( values.incentiveOffer );
		};

		return (
			<SkipButton
				disabled={ completing === ACTION_COMPLETE }
				isValidForm={ isValidForm }
				loading={ completing === ACTION_SKIP }
				onSkipCreatePaidAds={ handleSkipCreatePaidAds }
			/>
		);
	};

	const createContinueButton = ( formContext ) => {
		const { isValidForm, values } = formContext;
		const disabled =
			completing === ACTION_SKIP ||
			! isValidForm ||
			! isBillingCompleted ||
			incentiveLoading;

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
				disabled={ disabled }
				loading={ completing === ACTION_COMPLETE }
				onClick={ handleClick }
				text={ __( 'Complete setup', 'google-listings-and-ads' ) }
				isPrimary
			/>
		);
	};

	if ( ! countryCodes ) {
		return <AppSpinner />;
	}

	const paidAds = clientSession.getCampaign();

	const handleSubmit = async ( values ) => {
		const {
			level,
			dailyBudget,
			incentiveOffer,
			hasConfirmedEuPoliticalContent,
		} = values;

		setCompleting( ACTION_COMPLETE );

		const applied = await applyIncentive( incentiveOffer );
		if ( applied ) {
			recordGlaEvent( 'gla_onboarding_with_cyo_incentive_selected', {
				context: CONTEXT_EXTENSION_ONBOARDING,
				level: incentiveOffer,
			} );
		}

		const onBeforeFinish = handleSetupComplete.bind(
			null,
			dailyBudget,
			countryCodes,
			hasConfirmedEuPoliticalContent
		);

		recordGlaEvent(
			'gla_onboarding_complete_with_paid_ads_button_click',
			getEventProps( {
				level,
				budget: dailyBudget,
				audiences: countryCodes.join( ',' ),
				has_confirmed_eu_political_content:
					hasConfirmedEuPoliticalContent,
			} )
		);

		await finishOnboardingSetup( onBeforeFinish );
	};

	return (
		<CampaignAssetsForm
			countryCodes={ countryCodes }
			initialCampaign={ paidAds }
			onChange={ ( _, values ) => {
				clientSession.setCampaign( values );
			} }
			onSubmit={ handleSubmit }
		>
			<AdsCampaign
				context={ CONTEXT_EXTENSION_ONBOARDING }
				continueButton={ createContinueButton }
				headerTitle={ __(
					'Create a campaign to advertise your products',
					'google-listings-and-ads'
				) }
				skipButton={ createSkipButton }
			/>
			<BudgetIncentivePrompt
				countryCodes={ countryCodes }
				ref={ budgetPromptRef }
			/>
		</CampaignAssetsForm>
	);
}
