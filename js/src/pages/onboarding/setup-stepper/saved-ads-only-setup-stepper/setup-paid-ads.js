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
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { ACTION_COMPLETE, ACTION_SKIP } from '../constants';
import SkipButton from '../skip-button';
import clientSession from '../clientSession';
import AppSpinner from '~/components/app-spinner';

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 * @param {Object} props
 * @param {Function} props.onSubmit Callback fired when the form is submitted.
 * @param {Function} props.onSkip Callback fired when the user chooses to skip creating paid ads.
 */
export default function SetupPaidAds( { onSubmit, onSkip } ) {
	const budgetPromptRef = useRef();
	const [ completing, setCompleting ] = useState( null );
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();

	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;

	const handleSkipCreatePaidAds = async () => {
		setCompleting( ACTION_SKIP );
		onSkip();
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

	return (
		<CampaignAssetsForm
			initialCampaign={ paidAds }
			countryCodes={ countryCodes }
			onChange={ ( _, values ) => {
				clientSession.setCampaign( values );
			} }
			onSubmit={ onSubmit }
		>
			<AdsCampaign
				headerTitle={ __(
					'Create a campaign to advertise your products',
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
