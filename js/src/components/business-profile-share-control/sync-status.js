/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, Notice, Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SYNC_OUTCOME_QUEUED } from './constants';

/**
 * @typedef {import('./types').SyncOutcome} SyncOutcome
 */

/**
 * Renders how the post's last send to Google Business Profile went: that it's on its way, with a
 * spinner, while the send is queued, or the message for an outcome that needs the merchant's
 * attention. Renders nothing otherwise.
 *
 * @param {Object} props Component props.
 * @param {SyncOutcome|null} props.syncOutcome The post's latest sync outcome.
 * @return {JSX.Element|null} The status, or `null` when there's nothing to show.
 */
export default function SyncStatus( { syncOutcome } ) {
	if ( syncOutcome?.outcome === SYNC_OUTCOME_QUEUED ) {
		return (
			<Flex justify="flex-start" gap={ 2 }>
				<Spinner />
				<span>
					{ __(
						'Sending to Google Business Profile…',
						'google-listings-and-ads'
					) }
				</span>
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
