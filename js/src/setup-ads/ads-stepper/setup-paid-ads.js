/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { isEqual } from 'lodash';
import { useState, useEffect } from '@wordpress/element';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AdsCampaign from '.~/components/paid-ads/ads-campaign';
import useGoogleAdsAccountBillingStatus from '.~/hooks/useGoogleAdsAccountBillingStatus';
import useAdminUrl from '.~/hooks/useAdminUrl';
import useNavigateAwayPromptEffect from '.~/hooks/useNavigateAwayPromptEffect';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import useAdsSetupCompleteCallback from '.~/hooks/useAdsSetupCompleteCallback';
import CampaignAssetsForm from '.~/components/paid-ads/campaign-assets-form';
import { recordGlaEvent } from '.~/utils/tracks';
import useFetchBudgetRecommendation from '.~/hooks/useFetchBudgetRecommendation';
import AppSpinner from '.~/components/app-spinner';
import { GOOGLE_ADS_BILLING_STATUS } from '.~/constants';

const { APPROVED } = GOOGLE_ADS_BILLING_STATUS;

/**
 * Renders the step to setup paid ads
 *
 * @fires gla_launch_paid_campaign_button_click on submit
 */
const SetupPaidAds = () => {
	const { billingStatus } = useGoogleAdsAccountBillingStatus();
	const [ didFormChanged, setFormChanged ] = useState( false );
	const [ isSubmitted, setSubmitted ] = useState( false );
	const [ handleSetupComplete, isSubmitting ] = useAdsSetupCompleteCallback();
	const adminUrl = useAdminUrl();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const { highestDailyBudget, hasFinishedResolution } =
		useFetchBudgetRecommendation( countryCodes );

	const initialValues = {
		amount: highestDailyBudget,
	};

	useEffect( () => {
		if ( isSubmitted ) {
			// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
			const nextPath = getNewPath(
				{ guide: 'campaign-creation-success' },
				'/google/dashboard'
			);
			window.location.href = adminUrl + nextPath;
		}
	}, [ isSubmitted, adminUrl ] );

	const shouldPreventLeave = didFormChanged && ! isSubmitted;

	useNavigateAwayPromptEffect(
		__(
			'You have unsaved campaign data. Are you sure you want to leave?',
			'google-listings-and-ads'
		),
		shouldPreventLeave
	);

	const handleSubmit = ( values ) => {
		const { amount } = values;

		recordGlaEvent( 'gla_launch_paid_campaign_button_click', {
			audiences: countryCodes.join( ',' ),
			budget: amount,
		} );

		handleSetupComplete( amount, countryCodes, () => {
			setSubmitted( true );
		} );
	};

	const handleChange = ( _, values ) => {
		setFormChanged( ! isEqual( initialValues, values ) );
	};

	if ( ! countryCodes || ! hasFinishedResolution ) {
		return <AppSpinner />;
	}

	return (
		<CampaignAssetsForm
			initialCampaign={ initialValues }
			onChange={ handleChange }
			onSubmit={ handleSubmit }
			recommendedDailyBudget={ highestDailyBudget }
		>
			<AdsCampaign
				headerTitle={ __(
					'Create your paid campaign',
					'google-listings-and-ads'
				) }
				context="setup-ads"
				continueButton={ ( formContext ) => (
					<AppButton
						isPrimary
						text={ __(
							'Launch paid campaign',
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
