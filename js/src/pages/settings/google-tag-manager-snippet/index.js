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
import useGoogleTagManagerStatus from '~/hooks/useGoogleTagManagerStatus';
import useGoogleTagManagerSettings from '~/hooks/useGoogleTagManagerSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import Section from '~/components/section';
import SpinnerCard from '~/components/spinner-card';

/**
 * Renders the settings section for turning the Google Tag Manager container snippet on or off.
 * The toggle is disabled until a Google Tag Manager container is connected.
 */
const GoogleTagManagerSnippet = () => {
	const { isConnected } = useGoogleTagManagerStatus();
	const { settings, hasFinishedResolution } = useGoogleTagManagerSettings();
	const [ isSaving, setIsSaving ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();
	const { updateGoogleTagManagerSettings } = useAppDispatch();

	const handleChange = async ( enabled ) => {
		try {
			setIsSaving( true );
			await updateGoogleTagManagerSettings( {
				snippet_injection_enabled: enabled,
			} );

			createNotice(
				'success',
				__(
					'Google Tag Manager snippet setting updated successfully.',
					'google-listings-and-ads'
				)
			);
		} catch ( error ) {
			// Silently fail because the error is handled within `updateGoogleTagManagerSettings` action.
		} finally {
			setIsSaving( false );
		}
	};

	const helpText = isConnected
		? __(
				'Enable the Google Tag Manager snippet to allow for tracking on your store.',
				'google-listings-and-ads'
		  )
		: __(
				'Please connect your Google Tag Manager account in order to manage your script.',
				'google-listings-and-ads'
		  );

	return (
		<Section
			title={ __(
				'Google Tag Manager snippet',
				'google-listings-and-ads'
			) }
			description={ __(
				'Google Tag Manager manages tracking and other tags on your store.',
				'google-listings-and-ads'
			) }
		>
			{ ! hasFinishedResolution && <SpinnerCard /> }

			{ hasFinishedResolution && (
				<Section.Card>
					<Section.Card.Body>
						<ToggleControl
							label={ __(
								'Google Tag Manager snippet',
								'google-listings-and-ads'
							) }
							help={ helpText }
							checked={
								isConnected &&
								Boolean( settings?.snippetInjectionEnabled )
							}
							onChange={ handleChange }
							disabled={ ! isConnected || ! settings || isSaving }
						/>
					</Section.Card.Body>
				</Section.Card>
			) }
		</Section>
	);
};

export default GoogleTagManagerSnippet;
