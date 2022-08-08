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
import { getProductFeedUrl } from '.~/utils/urls';
import { GUIDE_NAMES } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';

export default function SetupPaidAds() {
	const adminUrl = useAdminUrl();
	const { createNotice } = useDispatchCoreNotices();
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
			<FaqsSection />
			<StepContentFooter>
				<Flex justify="right" gap={ 4 }>
					<AppButton
						isTertiary
						data-action="skip-ads"
						loading={ completing === 'skip-ads' }
						disabled={ completing === 'complete-ads' }
						onClick={ finishFreeListingsSetup }
					>
						{ __(
							'Skip paid ads creation',
							'google-listings-and-ads'
						) }
					</AppButton>
					<AppButton
						isPrimary
						data-action="complete-ads"
						loading={ completing === 'complete-ads' }
						disabled={ completing === 'skip-ads' }
						onClick={ handleCompleteClick }
					>
						{ __( 'Complete setup', 'google-listings-and-ads' ) }
					</AppButton>
				</Flex>
			</StepContentFooter>
		</StepContent>
	);
}
