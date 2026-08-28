/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AdsCampaign from '~/components/paid-ads/ads-campaign';
import BudgetIncentivePrompt from '~/components/paid-ads/budget-incentive-prompt';
import useGoogleAdsAccountBillingStatus from '~/hooks/useGoogleAdsAccountBillingStatus';
import useAdminUrl from '~/hooks/useAdminUrl';
import useNavigateAwayPromptEffect from '~/hooks/useNavigateAwayPromptEffect';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useAdsSetupCompleteCallback from '~/hooks/useAdsSetupCompleteCallback';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useEventPropertiesFilter from '~/hooks/useEventPropertiesFilter';
import CampaignAssetsForm from '~/components/paid-ads/campaign-assets-form';
import { FILTER_BUDGET_RECOMMENDATIONS, recordGlaEvent } from '~/utils/tracks';
import AppSpinner from '~/components/app-spinner';
import { GOOGLE_ADS_BILLING_STATUS } from '~/constants';
import useEuPoliticalDeclarationContext from '~/hooks/useEuPoliticalDeclarationContext';

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
	const budgetPromptRef = useRef();
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const [ handleSetupComplete, isSubmitting ] = useAdsSetupCompleteCallback();
	const adminUrl = useAdminUrl();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const { handleError: handleEuPoliticalDeclarationError } =
		useEuPoliticalDeclarationContext();
	const getEventProps = useEventPropertiesFilter(
		FILTER_BUDGET_RECOMMENDATIONS
	);

	const renderSubmitButton = ( formContext ) => {
		const handleClick = () => {
			budgetPromptRef.current
				.resolve( formContext.values.dailyBudget )
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
				disabled={
					! formContext.isValidForm ||
					billingStatus?.status !== APPROVED
				}
				loading={ isSubmitting }
				onClick={ handleClick }
				text={ __( 'Create campaign', 'google-listings-and-ads' ) }
				variant="primary"
			/>
		);
	};

	const handleSubmit = ( values ) => {
		const { level, dailyBudget, hasConfirmedEuPoliticalContent } = values;

		recordGlaEvent(
			'gla_launch_paid_campaign_button_click',
			getEventProps( {
				level,
				audiences: countryCodes.join( ',' ),
				budget: dailyBudget,
				has_confirmed_eu_political_content:
					hasConfirmedEuPoliticalContent,
			} )
		);

		handleSetupComplete(
			dailyBudget,
			countryCodes,
			hasConfirmedEuPoliticalContent,
			() => {
				// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
				const nextPath = getNewPath(
					{ guide: 'campaign-creation-success' },
					'/google/dashboard'
				);
				window.location.href = adminUrl + nextPath;
			}
		).catch( ( error ) => {
			handleEuPoliticalDeclarationError( error );
		} );
	};

	if ( ! countryCodes ) {
		return <AppSpinner />;
	}

	return (
		<CampaignAssetsForm
			countryCodes={ countryCodes }
			onSubmit={ handleSubmit }
		>
			<HookNavigateAwayPrompt />
			<AdsCampaign
				context="setup-ads"
				continueButton={ renderSubmitButton }
				headerTitle={ __(
					'Create your campaign',
					'google-listings-and-ads'
				) }
			/>
			<BudgetIncentivePrompt
				countryCodes={ countryCodes }
				ref={ budgetPromptRef }
			/>
		</CampaignAssetsForm>
	);
};

export default SetupPaidAds;
