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
 * Renders how the post's last send to Google Business Profile went: that it's on its way while the
 * send is queued, or the message for an outcome that needs the merchant's attention. Renders
 * nothing otherwise.
 *
 * @param {Object} props Component props.
 * @param {import('./hooks/useBusinessProfileSyncStatus').SyncOutcome|null} props.syncOutcome The post's latest sync outcome.
 * @param {boolean} props.isChecking Whether a queued send is still being checked on, shown with a spinner.
 * @return {JSX.Element|null} The status, or `null` when there's nothing to show.
 */
export default function SyncStatus( { syncOutcome, isChecking } ) {
	if ( syncOutcome?.outcome === SYNC_OUTCOME_QUEUED ) {
		return (
			<Flex justify="flex-start" gap={ 2 }>
				{ isChecking && <Spinner /> }
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
		<Notice
			status={ syncOutcome.display_status ?? 'error' }
			isDismissible={ false }
		>
			{ syncOutcome.display_message }
		</Notice>
	);
}
