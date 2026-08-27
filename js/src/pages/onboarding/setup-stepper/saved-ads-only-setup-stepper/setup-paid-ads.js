/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import BudgetIncentivePrompt from '~/components/paid-ads/budget-incentive-prompt';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AppButton from '~/components/app-button';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useApplyCYOIncentive from '~/hooks/useApplyCYOIncentive';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { ACTION_CONTINUE, ACTION_SKIP } from '../constants';
import {
	FILTER_BUDGET_RECOMMENDATIONS,
	CONTEXT_ADS_ONLY_ONBOARDING,
	recordGlaEvent,
} from '~/utils/tracks';
import SkipButton from '../skip-button';
import clientSession from '../clientSession';
import AppSpinner from '~/components/app-spinner';

/**
 * Selecting a "Choose Your Own" incentive offer when setting up paid ads during onboarding.
 *
 * @event gla_ads_only_onboarding_with_cyo_incentive_selected
 * @property {string} context The context in which the incentive offer is selected, e.g. 'create-ads', 'edit-ads', 'setup-ads', 'setup-mc', or 'setup-ads-only'.
 * @property {string} level The level of the selected incentive offer, e.g. 'low', 'medium', or 'high'.
 */

/**
 * Continuing the ads-only onboarding flow with a paid campaign configured.
 *
 * @event gla_ads_only_onboarding_with_paid_ads_continue_button_click
 * @property {string} level The selected level of the budget recommendation, e.g. 'low', 'recommended', 'high', 'custom'.
 * @property {number} budget The budget for the campaign.
 * @property {string} audiences The targeted audiences for the campaign.
 */

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 *
 * @fires gla_ads_only_onboarding_with_cyo_incentive_selected
 * @fires gla_ads_only_onboarding_with_paid_ads_continue_button_click
 *
 * @param {Object} props
 * @param {Function} props.onSubmit Callback fired when the user submits the paid ads creation form. Passes dailyBudget and hasConfirmedEuPoliticalContent.
 * @param {Function} props.onSkip Callback fired when the user chooses to skip creating paid ads.
 */
export default function SetupPaidAds( { onSubmit, onSkip } ) {
	const budgetPromptRef = useRef();
	const [ completing, setCompleting ] = useState( null );
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const { applyIncentive, loading: incentiveLoading } =
		useApplyCYOIncentive();
	const getEventProps = useEventPropertiesFilter(
		FILTER_BUDGET_RECOMMENDATIONS
	);

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const skipCreatePaidAds = async ( incentiveOffer ) => {
		setCompleting( ACTION_SKIP );

		const applied = await applyIncentive( incentiveOffer );
		if ( applied ) {
			recordGlaEvent(
				'gla_ads_only_onboarding_with_cyo_incentive_selected',
				{
					context: CONTEXT_ADS_ONLY_ONBOARDING,
					level: incentiveOffer,
				}
			);
		}

		onSkip();
	};

	const createSkipButton = ( formContext ) => {
		const { isValidForm, values } = formContext;

		const handleSkipCreatePaidAds = () => {
			skipCreatePaidAds( values.incentiveOffer );
		};

		return (
			<SkipButton
				disabled={ completing === ACTION_CONTINUE }
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
						formContext.setValues( {
							level: 'custom',
							amount,
						} );
					}
				} );
		};

		return (
			<AppButton
				disabled={ disabled }
				loading={ completing === ACTION_CONTINUE }
				onClick={ handleClick }
				text={ __( 'Continue', 'google-listings-and-ads' ) }
				isPrimary
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
			hasConfirmedEuPoliticalContent,
			incentiveOffer,
		} = values;

		setCompleting( ACTION_CONTINUE );

		const applied = await applyIncentive( incentiveOffer );
		if ( applied ) {
			recordGlaEvent(
				'gla_ads_only_onboarding_with_cyo_incentive_selected',
				{
					context: CONTEXT_ADS_ONLY_ONBOARDING,
					level: incentiveOffer,
				}
			);
		}

		recordGlaEvent(
			'gla_ads_only_onboarding_with_paid_ads_continue_button_click',
			getEventProps( {
				level,
				budget: dailyBudget,
				audiences: countryCodes.join( ',' ),
			} )
		);

		onSubmit( {
			dailyBudget,
			hasConfirmedEuPoliticalContent,
		} );
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
				context={ CONTEXT_ADS_ONLY_ONBOARDING }
				continueButton={ createContinueButton }
				headerTitle={ __(
					'Create a campaign to advertise your services',
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
