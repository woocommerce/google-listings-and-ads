/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import useAdminUrl from '.~/hooks/useAdminUrl';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import FaqsSection from '.~/components/paid-ads/faqs-section';
import AppButton from '.~/components/app-button';
import ProductFeedStatusSection from './product-feed-status-section';
import PaidAdsFeaturesSection from './paid-ads-features-section';
import GoogleAdsAccountSection from './google-ads-account-section';
import { getProductFeedUrl } from '.~/utils/urls';
import { GUIDE_NAMES } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';

function PaidAdsSectionsGroup() {
	// TODO: Add audience and budget sections.
	return <GoogleAdsAccountSection />;
}

export default function SetupPaidAds() {
	const adminUrl = useAdminUrl();
	const { createNotice } = useDispatchCoreNotices();
	const [ showPaidAdsSetup, setShowPaidAdsSetup ] = useState( false );
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

	// TODO: Add more disabled conditions to check
	// - Google Ads account connection
	// - Campaign data
	// - Billing setup
	const disabledComplete = completing === 'skip-ads';

	function createSkipButton( text ) {
		return (
			<AppButton
				isTertiary
				data-action="skip-ads"
				text={ text }
				loading={ completing === 'skip-ads' }
				disabled={ completing === 'complete-ads' }
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
						disabled={ completing === 'skip-ads' }
						onClick={ () => setShowPaidAdsSetup( true ) }
					/>
				}
			/>
			{ showPaidAdsSetup && <PaidAdsSectionsGroup /> }
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
						data-action="complete-ads"
						text={ __(
							'Complete setup',
							'google-listings-and-ads'
						) }
						loading={ completing === 'complete-ads' }
						disabled={ disabledComplete }
						onClick={ handleCompleteClick }
					/>
				</Flex>
			</StepContentFooter>
		</StepContent>
	);
}
