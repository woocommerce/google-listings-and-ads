/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { errorDescription } from './error-description';
import './index.scss';

/**
 * Renders the "reconnect" step's detail, shown when the Search Console connection has expired.
 *
 * @return {JSX.Element} The detail.
 */
export default function ReconnectDetail() {
	return (
		<Notice
			status="error"
			isDismissible={ false }
			className="gla-search-console-account-card__notice"
		>
			{ errorDescription(
				__(
					'<alert>Connection expired:</alert> Your Search Console connection needs to be re-authorized.',
					'google-listings-and-ads'
				)
			) }
		</Notice>
	);
}
