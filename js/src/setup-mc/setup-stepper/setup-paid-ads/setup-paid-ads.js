/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { Flex } from '@wordpress/components';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import useAdminUrl from '.~/hooks/useAdminUrl';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useAdsSetupCompleteCallback from '.~/hooks/useAdsSetupCompleteCallback';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import FaqsSection from '.~/components/paid-ads/faqs-section';
import AppButton from '.~/components/app-button';
import ProductFeedStatusSection from './product-feed-status-section';
import PaidAdsFeaturesSection from './paid-ads-features-section';
import PaidAdsSetupSections from './paid-ads-setup-sections';
import { getProductFeedUrl } from '.~/utils/urls';
import clientSession from './clientSession';
import { API_NAMESPACE } from '.~/data/constants';
import { GUIDE_NAMES } from '.~/constants';

const ACTION_COMPLETE = 'complete-ads';
const ACTION_SKIP = 'skip-ads';

/**
 * Renders the onboarding step for setting up the paid ads (Google Ads account and paid campaign)
 * or skipping it, and then completing the onboarding flow.
 */
export default function SetupPaidAds() {
	const adminUrl = useAdminUrl();
	const { createNotice } = useDispatchCoreNotices();
	const [ handleSetupComplete ] = useAdsSetupCompleteCallback();
	const [ showPaidAdsSetup, setShowPaidAdsSetup ] = useState( () =>
		clientSession.getShowPaidAdsSetup( false )
	);
	const [ paidAds, setPaidAds ] = useState( {} );
	const [ completing, setCompleting ] = useState( null );

	const handleContinuePaidAdsSetupClick = () => {
		setShowPaidAdsSetup( true );
		clientSession.setShowPaidAdsSetup( true );
	};

	const finishOnboardingSetup = async ( event, onBeforeFinish = noop ) => {
		setCompleting( event.target.dataset.action );

		try {
			await onBeforeFinish();
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
		const onBeforeFinish = handleSetupComplete.bind(
			null,
			paidAds.amount,
			paidAds.countryCodes
		);
		await finishOnboardingSetup( event, onBeforeFinish );
	};

	// The status check of Google Ads account connection is included in `paidAds.isReady`,
	// because when there is no connected account, it will disable the budget section and set the `amount` to `undefined`.
	const disabledComplete = completing === ACTION_SKIP || ! paidAds.isReady;

	function createSkipButton( text ) {
		return (
			<AppButton
				isTertiary
				data-action={ ACTION_SKIP }
				text={ text }
				loading={ completing === ACTION_SKIP }
				disabled={ completing === ACTION_COMPLETE }
				onClick={ finishOnboardingSetup }
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
						onClick={ handleContinuePaidAdsSetupClick }
					/>
				}
			/>
			{ showPaidAdsSetup && (
				<PaidAdsSetupSections onStatesReceived={ setPaidAds } />
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
