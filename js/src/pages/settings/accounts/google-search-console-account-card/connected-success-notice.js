/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { geReportsUrl } from '~/utils/urls';

const REPORTS_URL = geReportsUrl();

/**
 * Renders the one-time success notice shown when a Google Search Console property was just
 * auto-resolved and verified with no merchant action. Dismissible; once dismissed it stays
 * hidden for the rest of this page view — there's nothing to persist the dismissal against
 * server-side, since the backend only ever reports `just_resolved` on the one transitioning
 * call, never again afterward.
 *
 * @return {JSX.Element|null} The notice, or `null` once dismissed.
 */
export default function ConnectedSuccessNotice() {
	const [ isDismissed, setIsDismissed ] = useState( false );

	if ( isDismissed ) {
		return null;
	}

	return (
		<Notice status="success" onDismiss={ () => setIsDismissed( true ) }>
			<p>
				{ __(
					'We connected and verified a property for you. Your search data will start to appear over the next few days.',
					'google-listings-and-ads'
				) }
			</p>
			<AppButton href={ REPORTS_URL } isSecondary>
				{ __( 'View reports', 'google-listings-and-ads' ) }
			</AppButton>
		</Notice>
	);
}
