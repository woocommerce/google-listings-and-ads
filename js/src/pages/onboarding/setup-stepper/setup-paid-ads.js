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
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import BudgetIncentivePrompt from '~/components/paid-ads/budget-incentive-prompt';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AppButton from '~/components/app-button';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import { getProductFeedUrl } from '~/utils/urls';
import { handleApiError } from '~/utils/handleError';
import { FILTER_BUDGET_RECOMMENDATIONS, recordGlaEvent } from '~/utils/tracks';
import { useAppDispatch } from '~/data';
import {
	GUIDE_NAMES,
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
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 * @fires gla_onboarding_complete_with_paid_ads_button_click
 */
export default function SetupPaidAds() {
	const budgetPromptRef = useRef();
	const adminUrl = useAdminUrl();
	const [ completing, setCompleting ] = useState( null );
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const [ handleSetupComplete ] = useAdsSetupCompleteCallback();
	const { syncSettings } = useAppDispatch();
	const { handleError: handleEuPoliticalDeclarationError } =
		useEuPoliticalDeclarationContext();
	const {
		applyIncentive,
		redeemIncentive,
		result: incentiveResult,
	} = useApplyCYOIncentive();
	const {
		defaultIncentiveId,
		hasFinishedResolution: hasResolvedCyoIncentives,
	} = useCYOIncentives();
	const getEventProps = useEventPropertiesFilter(
		FILTER_BUDGET_RECOMMENDATIONS
	);

	const { data: incentives } = useCYOIncentives();
	const isServiceBasedMerchant = useServiceBasedMerchant();

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

	const skipCreatePaidAds = async ( incentiveId ) => {
		setCompleting( ACTION_SKIP );

		try {
			await applyIncentive( incentiveId );
		} catch ( error ) {
			setCompleting( null );
			return;
		}

		if ( incentiveId ) {
			const selectedIncentive = incentives?.find(
				( incentive ) =>
					String( incentive.id ) === String( incentiveId )
			);

			recordGlaEvent( 'gla_onboarding_with_cyo_incentive_selected', {
				is_service_based_merchant: isServiceBasedMerchant,
				level: selectedIncentive?.offer,
			} );
		}

		await finishOnboardingSetup();
	};

	const createSkipButton = ( formContext ) => {
		const { isValidForm, values } = formContext;

		const handleSkipCreatePaidAds = () => {
			skipCreatePaidAds( values.incentiveId );
		};

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
			completing === ACTION_SKIP ||
			! isValidForm ||
			incentiveResult.loading;

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
				text={ __( 'Complete setup', 'google-listings-and-ads' ) }
			/>
		);
	};

	if ( ! countryCodes || ! hasResolvedCyoIncentives ) {
		return <AppSpinner />;
	}

	const paidAds = clientSession.getCampaign();

	const handleSubmit = async ( values ) => {
		const {
			level,
			dailyBudget,
			incentiveId,
			hasConfirmedEuPoliticalContent,
		} = values;

		try {
			await applyIncentive( incentiveId );
			setCompleting( ACTION_COMPLETE );

			if ( incentiveId ) {
				const selectedIncentive = incentives?.find(
					( incentive ) =>
						String( incentive.id ) === String( incentiveId )
				);

				recordGlaEvent( 'gla_onboarding_with_cyo_incentive_selected', {
					is_service_based_merchant: isServiceBasedMerchant,
					level: selectedIncentive?.offer,
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
		} catch ( error ) {
			setCompleting( null );
		}
	};

	return (
		<CampaignAssetsForm
			initialCampaign={ { incentiveId: defaultIncentiveId, ...paidAds } }
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
				incentiveResult={ incentiveResult }
				onRetryIncentive={ redeemIncentive }
				context="setup-mc"
			/>
			<BudgetIncentivePrompt
				ref={ budgetPromptRef }
				countryCodes={ countryCodes }
			/>
		</CampaignAssetsForm>
	);
}
