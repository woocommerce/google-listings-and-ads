/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Notice } from '@wordpress/components';
import { store } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import usePreference from '~/hooks/usePreference';
import { PREFERENCES_STORE_NAMESPACE } from '~/constants';

const GOOGLE_SEARCH_CONSOLE_CONNECTED_DISMISSED_KEY =
	'google-search-console-connected-success-notice-dismissed';

/**
 * Renders the one-time success notice shown when a Google Search Console property was just
 * auto-resolved and verified with no merchant action. Dismissing it persists per-user forever.
 *
 * @return {JSX.Element|null} The notice, or `null` once dismissed.
 */
export default function ConnectedSuccessNotice() {
	const { set } = useDispatch( store );
	const isDismissed = usePreference(
		GOOGLE_SEARCH_CONSOLE_CONNECTED_DISMISSED_KEY
	);

	if ( isDismissed ) {
		return null;
	}

	const handleDismiss = () => {
		set(
			PREFERENCES_STORE_NAMESPACE,
			GOOGLE_SEARCH_CONSOLE_CONNECTED_DISMISSED_KEY,
			true
		);
	};

	return (
		<Notice status="success" onDismiss={ handleDismiss }>
			<p>
				{ __(
					'We connected and verified a property for you. Your search data will start to appear over the next few days.',
					'google-listings-and-ads'
				) }
			</p>
		</Notice>
	);
}
