/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import Section from '~/components/section';

/**
 * Renders the settings section for turning the Google Tag Manager container snippet on or off.
 * Only shown once a Google Tag Manager container is connected.
 */
const GoogleTagManagerSnippet = () => {
	const { account, hasFinishedResolution } = useGoogleTagManagerAccount();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateGoogleTagManagerSnippetInjection } = useAppDispatch();

	if (
		! hasFinishedResolution ||
		account?.status !== GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED
	) {
		return null;
	}

	const handleChange = async ( enabled ) => {
		try {
			setIsSaving( true );
			await updateGoogleTagManagerSnippetInjection( enabled );

			createNotice(
				'success',
				__(
					'Google Tag Manager snippet setting updated successfully.',
					'google-listings-and-ads'
				)
			);
		} catch ( error ) {
			// Silently fail because the error is handled within `updateGoogleTagManagerSnippetInjection` action.
		} finally {
			setIsSaving( false );
		}
	};

	return (
		<Section
			title={ __(
				'Google Tag Manager Snippet',
				'google-listings-and-ads'
			) }
			description={ __(
				'Google Tag Manager manages tracking and other tags on your store.',
				'google-listings-and-ads'
			) }
		>
			<Section.Card>
				<Section.Card.Body>
					<ToggleControl
						label={ __(
							'Google Tag Manager Snippet',
							'google-listings-and-ads'
						) }
						help={ __(
							'Enable the Google Tag Manager snippet to allow for tracking on your store.',
							'google-listings-and-ads'
						) }
						checked={ account.snippetInjectionEnabled }
						onChange={ handleChange }
						disabled={ isSaving }
					/>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default GoogleTagManagerSnippet;
