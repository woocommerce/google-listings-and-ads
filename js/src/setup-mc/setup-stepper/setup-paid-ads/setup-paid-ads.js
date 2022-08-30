/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useAdminUrl from '.~/hooks/useAdminUrl';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import FaqsSection from '.~/components/paid-ads/faqs-section';
import AppButton from '.~/components/app-button';
import ProductFeedStatusSection from './product-feed-status-section';
import PaidAdsFeaturesSection from './paid-ads-features-section';
import GoogleAdsAccountSection from './google-ads-account-section';
import AudienceSection from '.~/components/paid-ads/audience-section';
import BudgetSection from '.~/components/paid-ads/budget-section';
import validateForm from '.~/utils/paid-ads/validateForm';
import { getProductFeedUrl } from '.~/utils/urls';
import { GUIDE_NAMES } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';

function PaidAdsSectionsGroup( { onCampaignChange } ) {
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { data: targetAudience } = useTargetAudienceFinalCountryCodes();

	if ( ! targetAudience ) {
		return <GoogleAdsAccountSection />;
	}

	const handleChange = ( _, values, isValid ) => {
		onCampaignChange( { ...values, isValid } );
	};

	return (
		<Form
			initialValues={ {
				amount: 0,
				countryCodes: targetAudience,
			} }
			onChange={ handleChange }
			validate={ validateForm }
		>
			{ ( formProps ) => {
				const { countryCodes } = formProps.values;
				const disabledAudience =
					googleAdsAccount?.status !== 'connected';
				const disabledBudget =
					disabledAudience || countryCodes.length === 0;

				return (
					<>
						<GoogleAdsAccountSection />
						<AudienceSection
							formProps={ formProps }
							disabled={ disabledAudience }
						/>
						<BudgetSection
							formProps={ formProps }
							disabled={ disabledBudget }
						/>
					</>
				);
			} }
		</Form>
	);
}

const ACTION_COMPLETE = 'complete-ads';
const ACTION_SKIP = 'skip-ads';

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 */
export default function SetupPaidAds() {
	const adminUrl = useAdminUrl();
	const { createNotice } = useDispatchCoreNotices();
	const [ showPaidAdsSetup, setShowPaidAdsSetup ] = useState( false );
	const [ campaign, setCampaign ] = useState( {} );
	const [ completing, setCompleting ] = useState( null );

	const finishFreeListingsSetup = async ( event ) => {
		setCompleting( event.target.dataset.action );

		try {
			await apiFetch( {
				path: `${ API_NAMESPACE }/mc/settings/sync`,
				method: 'POST',
			} );
		} catch ( e ) {
			setCompleting( null );

			createNotice(
				'error',
				__(
					'Unable to complete your setup.',
					'google-listings-and-ads'
				)
			);
		}

		// Force reload WC admin page to initiate the relevant dependencies of the Dashboard page.
		const query = { guide: GUIDE_NAMES.SUBMISSION_SUCCESS };
		window.location.href = adminUrl + getProductFeedUrl( query );
	};

	const handleCompleteClick = async ( event ) => {
		// TODO: Implement the compaign creation and paid ads completion.
		await finishFreeListingsSetup( event );
	};

	// The status check of Google Ads account connection is included in `campaign.isValid`,
	// because when there is no connected account, it will disable the budget section and set the `amount` to `undefined`.
	// TODO: Add a condition to check Billing setup
	const disabledComplete = completing === ACTION_SKIP || ! campaign.isValid;

	function createSkipButton( text ) {
		return (
			<AppButton
				isTertiary
				data-action={ ACTION_SKIP }
				text={ text }
				loading={ completing === ACTION_SKIP }
				disabled={ completing === ACTION_COMPLETE }
				onClick={ finishFreeListingsSetup }
			/>
		);
	}

	return (
		<StepContent>
			<StepContentHeader
				title={ __(
					'Complete your campaign with paid ads',
					'google-listings-and-ads'
				) }
				description={ __(
					'As soon as your products are approved, your listings and ads will be live. In the meantime, let’s set up your ads.',
					'google-listings-and-ads'
				) }
			/>
			<ProductFeedStatusSection />
			<PaidAdsFeaturesSection
				hideFooterButtons={ showPaidAdsSetup }
				skipButton={ createSkipButton(
					__( 'Skip this step for now', 'google-listings-and-ads' )
				) }
				continueButton={
					<AppButton
						isPrimary
						text={ __(
							'Create a paid ad campaign',
							'google-listings-and-ads'
						) }
						disabled={ completing === ACTION_SKIP }
						onClick={ () => setShowPaidAdsSetup( true ) }
					/>
				}
			/>
			{ showPaidAdsSetup && (
				<PaidAdsSectionsGroup onCampaignChange={ setCampaign } />
			) }
			<FaqsSection />
			<StepContentFooter hidden={ ! showPaidAdsSetup }>
				<Flex justify="right" gap={ 4 }>
					{ createSkipButton(
						__(
							'Skip paid ads creation',
							'google-listings-and-ads'
						)
					) }
					<AppButton
						isPrimary
						data-action={ ACTION_COMPLETE }
						text={ __(
							'Complete setup',
							'google-listings-and-ads'
						) }
						loading={ completing === ACTION_COMPLETE }
						disabled={ disabledComplete }
						onClick={ handleCompleteClick }
					/>
				</Flex>
			</StepContentFooter>
		</StepContent>
	);
}
