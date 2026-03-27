/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useApplyIncentive from '~/hooks/useApplyIncentive';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import BudgetIncentivePrompt from '~/components/paid-ads/budget-incentive-prompt';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import AppButton from '~/components/app-button';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
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
	const applyIncentive = useApplyIncentive();
	const getEventProps = useEventPropertiesFilter(
		FILTER_BUDGET_RECOMMENDATIONS
	);

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const handleSkipCreatePaidAds = async ( incentiveId ) => {
		setCompleting( ACTION_SKIP );
		if ( ! ( await applyIncentive( incentiveId ) ) ) {
			setCompleting( null );
			return;
		}
		onSkip();
	};

	const createSkipButton = ( formContext ) => {
		const { isValidForm, values } = formContext;

		return (
			<SkipButton
				isValidForm={ isValidForm }
				onSkipCreatePaidAds={ () =>
					handleSkipCreatePaidAds( values.incentiveId )
				}
				disabled={ completing === ACTION_CONTINUE }
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
				loading={ completing === ACTION_CONTINUE }
				text={ __( 'Continue', 'google-listings-and-ads' ) }
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
			initialCampaign={ paidAds }
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
				context="setup-ads-only"
			/>
			<BudgetIncentivePrompt
				ref={ budgetPromptRef }
				countryCodes={ countryCodes }
			/>
		</CampaignAssetsForm>
	);
}
