/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppNotice from '~/components/app-notice';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useGoogleTagManagerStatus from '~/hooks/useGoogleTagManagerStatus';

/**
 * Warns that the connected Google Tag Manager container contains a Google Ads conversion tag.
 * Shown for as long as the conflict is detected.
 *
 * @return {JSX.Element|null} The notice, or `null` when there's no conflict.
 */
const AdsConversionConflictNotice = () => {
	const { isConnected } = useGoogleTagManagerStatus();
	const { account } = useGoogleTagManagerAccount();

	if ( ! isConnected || ! account.adsConversionConflict ) {
		return null;
	}

	return (
		<AppNotice status="warning" isDismissible={ false }>
			{ sprintf(
				// translators: %s: the connected Google Tag Manager container's public ID, e.g. GTM-ABC1234.
				__(
					'The connected Google Tag Manager container (%s) contains a Google Ads Conversion script. Google Ads events are already captured natively by the plugin. Enabling this tag can lead to duplicate events registration.',
					'google-listings-and-ads'
				),
				account.containerPublicId
			) }
		</AppNotice>
	);
};

export default AdsConversionConflictNotice;
