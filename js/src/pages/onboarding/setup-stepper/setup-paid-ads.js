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
import useApplyIncentive from '~/hooks/useApplyIncentive';
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useServiceBasedMerchant from '~/hooks/useServiceBasedMerchant';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import BudgetIncentivePrompt from '~/components/paid-ads/budget-incentive-prompt';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AppButton from '~/components/app-button';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import { getProductFeedUrl } from '~/utils/urls';
import { handleApiError } from '~/utils/handleError';
import { FILTER_BUDGET_RECOMMENDATIONS, recordGlaEvent } from '~/utils/tracks';
import { useAppDispatch } from '~/data';
import { GUIDE_NAMES, GOOGLE_ADS_BILLING_STATUS } from '~/constants';
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
 * @fires gla_onboarding_complete_with_paid_ads_button_click
 */
export default function SetupPaidAds() {
	const budgetPromptRef = useRef();
	const adminUrl = useAdminUrl();
	const [ completing, setCompleting ] = useState( null );
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const [ handleSetupComplete ] = useAdsSetupCompleteCallback();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { syncSettings } = useAppDispatch();
	const applyIncentive = useApplyIncentive();
	const getEventProps = useEventPropertiesFilter(
		FILTER_BUDGET_RECOMMENDATIONS
	);

	const { data: incentives } = useCYOIncentives();
	const isServiceBasedMerchant = useServiceBasedMerchant();

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const finishOnboardingSetup = async ( onBeforeFinish = noop ) => {
		try {
			await syncSettings();
			await onBeforeFinish();
		} catch ( e ) {
			setCompleting( null );

			handleApiError(
				e,
				__(
					'Unable to complete your setup.',
					'google-listings-and-ads'
				)
			);
			return;
		}

		// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getProductFeedUrl( query );
	};

	const handleSkipCreatePaidAds = async ( incentiveId ) => {
		setCompleting( ACTION_SKIP );
		if ( ! ( await applyIncentive( incentiveId ) ) ) {
			setCompleting( null );
			return;
		}
		if ( incentiveId ) {
			const incentive = incentives?.find(
				( i ) => String( i.id ) === String( incentiveId )
			);
			recordGlaEvent( 'gla_onboarding_with_cyo_incentive_selected', {
				is_service_based_merchant: isServiceBasedMerchant,
				offer: incentive?.offer,
			} );
		}
		await finishOnboardingSetup();
	};

	const createSkipButton = ( formContext ) => {
		const { isValidForm, values } = formContext;

		return (
			<SkipButton
				isValidForm={ isValidForm }
				onSkipCreatePaidAds={ () =>
					handleSkipCreatePaidAds( values.incentiveId )
				}
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
				text={ __( 'Complete setup', 'google-listings-and-ads' ) }
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
		const {
			level,
			dailyBudget,
			incentiveId,
			hasConfirmedEuPoliticalContent,
		} = values;

		if ( ! ( await applyIncentive( incentiveId ) ) ) {
			return;
		}

		if ( incentiveId ) {
			const incentive = incentives?.find(
				( i ) => String( i.id ) === String( incentiveId )
			);
			recordGlaEvent( 'gla_onboarding_with_cyo_incentive_selected', {
				is_service_based_merchant: isServiceBasedMerchant,
				offer: incentive?.offer,
			} );
		}

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
				context="setup-mc"
			/>
			<BudgetIncentivePrompt
				ref={ budgetPromptRef }
				countryCodes={ countryCodes }
			/>
		</CampaignAssetsForm>
	);
}
