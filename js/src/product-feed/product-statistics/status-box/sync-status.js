/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { format as formatDate } from '@wordpress/date';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import Status from '.~/product-feed/product-statistics/status-box/status';
import { glaData } from '.~/constants';
import SyncIcon from '.~/components/sync-icon';
import SuccessIcon from '.~/components/success-icon';

function getSyncResult( {
	scheduled_sync: scheduledSync,
	statistics,
	timestamp,
} ) {
	if ( scheduledSync !== 0 ) {
		return {
			Icon: SyncIcon,
			status: __( 'Sync in progress', 'google-listings-and-ads' ),
			description: null,
		};
	}

	const totalSynced = Object.entries( statistics ).reduce(
		( sum, [ key, num ] ) => {
			if ( key === 'not_synced' ) {
				return sum;
			}
			return sum + num;
		},
		0
	);

	return {
		Icon: SuccessIcon,
		status: __(
			'Automatically synced to Google',
			'google-listings-and-ads'
		),
		description: sprintf(
			// translators: %s: datetime of last update products sync status, and %d: number of synced products, with minimum value of 1.
			_n(
				'Last updated: %1$s, containing %2$d product',
				'Last updated: %1$s, containing %2$d products',
				totalSynced,
				'google-listings-and-ads'
			),
			formatDate(
				glaData.dateFormat +
					( glaData.dateFormat.trim() && glaData.timeFormat.trim()
						? ', '
						: '' ) +
					glaData.timeFormat,
				new Date( timestamp * 1000 )
			),
			totalSynced
		),
	};
}

function SyncStatus() {
	const { data } = useAppSelectDispatch( 'getMCProductStatistics' );
	const { Icon, status, description } = getSyncResult( data );

	return (
		<Status
			title={ __( 'Sync with Google:', 'google-listings-and-ads' ) }
			icon={ <Icon size={ 24 } /> }
			label={ <span className="gla-status-success">{ status }</span> }
			description={ description }
		/>
	);
}

export default SyncStatus;
