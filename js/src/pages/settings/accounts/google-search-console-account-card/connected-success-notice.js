/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Renders the one-time success notice shown when a Google Search Console property was just
 * auto-resolved and verified with no merchant action. Not dismissible.
 *
 * @return {JSX.Element} The notice.
 */
export default function ConnectedSuccessNotice() {
	return (
		<Notice status="success" isDismissible={ false }>
			<p>
				{ __(
					'We connected and verified a property for you. Your search data will start to appear over the next few days.',
					'google-listings-and-ads'
				) }
			</p>
		</Notice>
	);
}
