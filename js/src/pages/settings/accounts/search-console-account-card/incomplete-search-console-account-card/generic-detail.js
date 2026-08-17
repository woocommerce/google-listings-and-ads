/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders the generic fallback detail for an abandoned connect flow that isn't covered by a
 * more specific step.
 *
 * @return {JSX.Element} The detail.
 */
export default function GenericDetail() {
	return (
		<div className="gla-search-console-account-card__detail-text">
			{ __(
				"Your Search Console connection isn't complete yet.",
				'google-listings-and-ads'
			) }
		</div>
	);
}
