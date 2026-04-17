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
import useCYOIncentives from '~/hooks/useCYOIncentives';
import useApplyCYOIncentive from '~/hooks/useApplyCYOIncentive';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { ACTION_CONTINUE, ACTION_SKIP } from '../constants';
import { FILTER_BUDGET_RECOMMENDATIONS, recordGlaEvent } from '~/utils/tracks';
import SkipButton from '../skip-button';
import clientSession from '../clientSession';
import AppSpinner from '~/components/app-spinner';

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 * @param {Object} props
 * @param {Function} props.onSubmit Callback fired when the user submits the paid ads creation form. Passes dailyBudget and hasConfirmedEuPoliticalContent.
 * @param {Function} props.onSkip Callback fired when the user chooses to skip creating paid ads.
 */
export default function SetupPaidAds( { onSubmit, onSkip } ) {
	const budgetPromptRef = useRef();
	const [ completing, setCompleting ] = useState( null );
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
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

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const skipCreatePaidAds = async ( incentiveId ) => {
		setCompleting( ACTION_SKIP );

		try {
			await applyIncentive( incentiveId );
		} catch ( error ) {
			setCompleting( null );
			return;
		}
		onSkip();
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
				disabled={ completing === ACTION_CONTINUE }
				loading={ completing === ACTION_SKIP }
			/>
		);
	};

	const createContinueButton = ( formContext ) => {
		const { isValidForm, values } = formContext;
		const disabled =
			completing === ACTION_SKIP ||
			! isValidForm ||
			! isBillingCompleted ||
			incentiveResult.loading;

		const handleClick = async () => {
			try {
				await applyIncentive( values.incentiveId );

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
			} catch ( error ) {}
		};

		return (
			<AppButton
				isPrimary
				disabled={ disabled }
				onClick={ handleClick }
				loading={
					completing === ACTION_CONTINUE || incentiveResult.loading
				}
				text={ __( 'Continue', 'google-listings-and-ads' ) }
			/>
		);
	};

	const paidAds = {
		...clientSession.getCampaign(),
	};

	if ( ! countryCodes || ! hasResolvedCyoIncentives ) {
		return <AppSpinner />;
	}

	const handleSubmit = async ( values ) => {
		const { level, dailyBudget, hasConfirmedEuPoliticalContent } = values;

		setCompleting( ACTION_CONTINUE );

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
			initialCampaign={ { incentiveId: defaultIncentiveId, ...paidAds } }
			countryCodes={ countryCodes }
			onChange={ ( _, values ) => {
				clientSession.setCampaign( values );
			} }
			onSubmit={ handleSubmit }
		>
			<AdsCampaign
				headerTitle={ __(
					'Create a campaign to advertise your services',
					'google-listings-and-ads'
				) }
				continueButton={ createContinueButton }
				skipButton={ createSkipButton }
				incentiveResult={ incentiveResult }
				onRetryIncentive={ redeemIncentive }
				context="setup-ads-only"
			/>
			<BudgetIncentivePrompt
				ref={ budgetPromptRef }
				countryCodes={ countryCodes }
			/>
		</CampaignAssetsForm>
	);
}
