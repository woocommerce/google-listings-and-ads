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
 * Renders the "connection failed" step's detail, shown when the initial connect attempt failed.
 *
 * @return {JSX.Element} The detail.
 */
export default function ConnectionFailedDetail() {
	return (
		<Notice
			status="error"
			isDismissible={ false }
			className="gla-search-console-account-card__notice"
		>
			{ errorDescription(
				__(
					"<alert>Connection failed:</alert> We couldn't connect your Search Console account. Please try again.",
					'google-listings-and-ads'
				)
			) }
		</Notice>
	);
}
