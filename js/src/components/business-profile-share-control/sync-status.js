/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, Notice, Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SYNC_OUTCOME_QUEUED, SYNC_OUTCOME_UNSHARE_QUEUED } from './constants';

/**
 * @typedef {import('./types').SyncOutcome} SyncOutcome
 */

/**
 * Renders how the post's last send to, or removal from, Google Business Profile went: that it's
 * in progress, with a spinner, while it's queued, or the message for an outcome that needs the
 * merchant's attention. Renders nothing otherwise.
 *
 * @param {Object} props Component props.
 * @param {SyncOutcome|null} props.syncOutcome The post's latest sync outcome.
 * @return {JSX.Element|null} The status, or `null` when there's nothing to show.
 */
export default function SyncStatus( { syncOutcome } ) {
	const pendingText = {
		[ SYNC_OUTCOME_QUEUED ]: __(
			'Sending to Google Business Profile…',
			'google-listings-and-ads'
		),
		[ SYNC_OUTCOME_UNSHARE_QUEUED ]: __(
			'Removing from Google Business Profile…',
			'google-listings-and-ads'
		),
	}[ syncOutcome?.outcome ];

	if ( pendingText ) {
		return (
			<Flex justify="flex-start" gap={ 2 }>
				<Spinner />
				<span>{ pendingText }</span>
			</Flex>
		);
	}

	if ( ! syncOutcome?.display_message ) {
		return null;
	}

	return (
		<Notice status={ syncOutcome.display_status } isDismissible={ false }>
			{ syncOutcome.display_message }
		</Notice>
	);
}
