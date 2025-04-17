/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useAdminUrl from '~/hooks/useAdminUrl';
import useNavigateAwayPromptEffect from '~/hooks/useNavigateAwayPromptEffect';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useAdsSetupCompleteCallback from '~/hooks/useAdsSetupCompleteCallback';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import { recordGlaEvent } from '~/utils/tracks';
import useBudgetRecommendation from '~/hooks/useBudgetRecommendation';
import AppSpinner from '~/components/app-spinner';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';

const { APPROVED } = GOOGLE_ADS_BILLING_STATUS;

function HookNavigateAwayPrompt() {
	const { isDirty, adapter } = useAdaptiveFormContext();
	const shouldPreventLeave = isDirty && ! adapter.isSubmitted;

	useNavigateAwayPromptEffect(
		__(
			'You have unsaved campaign data. Are you sure you want to leave?',
			'google-listings-and-ads'
		),
		shouldPreventLeave
	);

	return null;
}

/**
 * Renders the step to setup paid ads
 *
 * @fires gla_launch_paid_campaign_button_click on submit
 */
const SetupPaidAds = () => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const [ handleSetupComplete, isSubmitting ] = useAdsSetupCompleteCallback();
	const adminUrl = useAdminUrl();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const { recommendedDailyBudget, hasFinishedResolution } =
		useBudgetRecommendation( countryCodes );

	const initialValues = {
		amount: recommendedDailyBudget,
	};

	const handleSubmit = ( values ) => {
		const { amount } = values;

		recordGlaEvent( 'gla_launch_paid_campaign_button_click', {
			audiences: countryCodes.join( ',' ),
			budget: amount,
		} );

		handleSetupComplete( amount, countryCodes, () => {
			// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
			const nextPath = getNewPath(
				{ guide: 'campaign-creation-success' },
				'/google/dashboard'
			);
			window.location.href = adminUrl + nextPath;
		} );
	};

	if ( ! countryCodes || ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	return (
		<CampaignAssetsForm
			initialCampaign={ initialValues }
			recommendedDailyBudget={ recommendedDailyBudget }
			onSubmit={ handleSubmit }
		>
			<HookNavigateAwayPrompt />
			<AdsCampaign
				headerTitle={ __(
					'Create your campaign',
					'google-listings-and-ads'
				) }
				context="setup-ads"
				continueButton={ ( formContext ) => (
					<AppButton
						isPrimary
						text={ __(
							'Create campaign',
							'google-listings-and-ads'
						) }
						disabled={
							! formContext.isValidForm ||
							billingStatus?.status !== APPROVED
						}
						loading={ isSubmitting }
						onClick={ formContext.handleSubmit }
					/>
				) }
			/>
		</CampaignAssetsForm>
	);
};

export default SetupPaidAds;
