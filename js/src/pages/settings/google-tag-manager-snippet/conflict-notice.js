/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppNotice from '~/components/app-notice';

/**
 * Warns that the connected Google Tag Manager container already contains a Google Ads conversion
 * tag. Dismissing it only hides it until the page is reloaded.
 *
 * @param {Object} props Component props.
 * @param {string} props.containerPublicId The connected container's public ID (`GTM-XXXXXXX`).
 * @return {JSX.Element|null} The notice, or `null` once dismissed.
 */
const ConflictNotice = ( { containerPublicId } ) => {
	const [ isDismissed, setIsDismissed ] = useState( false );

	if ( isDismissed ) {
		return null;
	}

	const handleRemove = () => {
		setIsDismissed( true );
	};

	return (
		<AppNotice status="warning" onRemove={ handleRemove } isDismissible>
			{ sprintf(
				// translators: %s: the connected Google Tag Manager container's public ID, e.g. GTM-ABC1234.
				__(
					'The connected Google Tag Manager container (%s) contains a Google Ads Conversion script. Google Ads events are already captured natively by the plugin. Enabling this tag can lead to duplicate events registration.',
					'google-listings-and-ads'
				),
				containerPublicId
			) }
		</AppNotice>
	);
};

export default ConflictNotice;
