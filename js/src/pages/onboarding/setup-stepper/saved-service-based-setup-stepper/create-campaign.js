/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Stepper } from '@woocommerce/components';
import { getHistory } from '@woocommerce/navigation';
import { useState, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useAdminUrl from '~/hooks/useAdminUrl';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import useTargetAudienceWithSuggestions from '../useTargetAudienceWithSuggestions';
import BudgetIncentivePrompt from '~/components/paid-ads/budget-incentive-prompt';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import SetupServiceBasedAccounts from '../setup-service-based-accounts';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import AppButton from '~/components/app-button';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import SkipButton from '../skip-button';
import clientSession from '../clientSession';
import convertToAssetGroupUpdateBody from '~/components/paid-ads/convertToAssetGroupUpdateBody';
import AssetGroup, {
	ACTION_SUBMIT_CAMPAIGN_AND_ASSETS,
} from '~/components/paid-ads/asset-group';
import { SERVICE_BASED_STEP_NAME_KEY_MAP, ACTION_SKIP } from '../constants';
import { API_NAMESPACE } from '~/data/constants';
import { GUIDE_NAMES, GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import { getDashboardUrl } from '~/utils/urls';
import {
	recordStepperChangeEvent,
	recordStepContinueEvent,
	FILTER_ONBOARDING,
	CONTEXT_SERVICE_BASED_ONBOARDING,
} from '~/utils/tracks';

const CreateCampaign = ( { onContinue } ) => {
	const adminUrl = useAdminUrl();
	const budgetPromptRef = useRef();
	const [ completing, setCompleting ] = useState( null );
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const isBillingCompleted =
		billingStatus?.status === GOOGLE_ADS_BILLING_STATUS.APPROVED;
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();

	const paidAds = {
		...clientSession.getCampaign(),
	};

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
			/>
		);
	};

	const handleOnCreateCampaignContinueClick = ( formContext ) => {
		const level = formContext.values.level;
		let userDailyBudget = formContext.values.amount;

		if ( level !== 'custom' ) {
			userDailyBudget =
				formContext.adapter.budgetRecommendation[ level ].dailyBudget;
		}

		onContinue( userDailyBudget );
	};

	const createContinueButton = ( formContext ) => {
		const { isValidForm, values } = formContext;
		const disabled =
			completing === ACTION_SKIP || ! isValidForm || ! isBillingCompleted;

		const handleClick = () => {
			budgetPromptRef.current
				.resolve( values.dailyBudget )
				.then( ( amount ) => {
					if ( Number.isFinite( amount ) ) {
						formContext.setValues( {
							level: 'custom',
							amount,
						} );
					}

					handleOnCreateCampaignContinueClick( formContext );
				} );
		};

		return (
			<AppButton
				isPrimary
				disabled={ disabled }
				onClick={ handleClick }
				text={ __( 'Continue', 'google-listings-and-ads' ) }
			/>
		);
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
