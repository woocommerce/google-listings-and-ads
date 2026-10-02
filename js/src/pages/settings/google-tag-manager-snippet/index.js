/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerSettings from '~/hooks/useGoogleTagManagerSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import Section from '~/components/section';
import SpinnerCard from '~/components/spinner-card';
import ConflictNotice from './conflict-notice';

/**
 * Renders the settings section for turning the Google Tag Manager container snippet on or off.
 * The toggle is disabled until a Google Tag Manager container is connected, and a warning is shown
 * above it when the connected container already contains a Google Ads conversion tag.
 */
const GoogleTagManagerSnippet = () => {
	const { account, hasFinishedResolution: hasResolvedAccount } =
		useGoogleTagManagerAccount();
	const { settings, hasFinishedResolution: hasResolvedSettings } =
		useGoogleTagManagerSettings();
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

	const isLoaded = hasResolvedAccount && hasResolvedSettings;
	const isConnected =
		account?.status === GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED;

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
			{ ! isLoaded && <SpinnerCard /> }

			{ isLoaded && (
				<Section.Card>
					<Section.Card.Body>
						<Flex direction="column" gap={ 4 }>
							<ConflictNotice />
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
								disabled={
									! isConnected || ! settings || isSaving
								}
							/>
						</Flex>
					</Section.Card.Body>
				</Section.Card>
			) }
		</Section>
	);
};

export default GoogleTagManagerSnippet;
