/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCardTextDetail from '../../../account-card-text-detail';

/**
 * Renders the generic fallback detail for an abandoned connect flow that isn't covered by a
 * more specific step.
 *
 * @return {JSX.Element} The detail.
 */
export default function Generic() {
	return (
		<AccountCardTextDetail>
			{ __(
				"Your Google Search Console connection isn't complete yet.",
				'google-listings-and-ads'
			) }
		</AccountCardTextDetail>
	);
}
