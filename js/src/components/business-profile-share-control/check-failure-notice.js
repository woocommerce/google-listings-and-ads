/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CHECK_FAILURE_RECONNECT } from './constants';

/**
 * Renders the warning shown when the editor couldn't check whether the post still exists on
 * Google Business Profile. Nothing renders when the check didn't fail.
 *
 * @param {Object} props Component props.
 * @param {'reconnect'|'error'|null} props.checkFailure Why the check failed, or `null` when it didn't.
 * @return {JSX.Element|null} The warning, or `null` when there's nothing to show.
 */
export default function CheckFailureNotice( { checkFailure } ) {
	if ( ! checkFailure ) {
		return null;
	}

	const message =
		checkFailure === CHECK_FAILURE_RECONNECT
			? __(
					'We couldn’t check whether this post still exists on Google Business Profile. Reconnect your Google account to check it.',
					'google-listings-and-ads'
				)
			: __(
					'We couldn’t check whether this post still exists on Google Business Profile.',
					'google-listings-and-ads'
				);

	return (
		<Notice status="warning" isDismissible={ false }>
			{ message }
		</Notice>
	);
}
