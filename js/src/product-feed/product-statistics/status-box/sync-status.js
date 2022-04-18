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

/**
 * Returns the translatable text as well as the icon an description for the Sync Status
 * based on the `scheduled_sync` value as the synced number of products.
 *
 * @param {Object} data Data with the sync information
 * @param {number} data.scheduled_sync Amount of scheduled jobs which will sync products to Google.
 * @param {Object} data.statistics Merchant Center product status statistics information.
 * @param {number} data.timestamp Timestamp reflecting when the product status statistics were last generated.
 * @return {string} Translation for the number of issues
 */
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

/**
 * Renders status information for the Product Sync
 *
 * @return {JSX.Element} The status for the Product Sync
 */
function SyncStatus() {
	const { data } = useAppSelectDispatch( 'getMCProductStatistics' );
	const { Icon, status, description } = getSyncResult( data );

	return (
		<Status
			title={ __( 'Sync with Google:', 'google-listings-and-ads' ) }
			icon={ <Icon className="gla-success" size={ 24 } /> }
			label={ <span className="gla-success">{ status }</span> }
			description={ description }
		/>
	);
}

export default SyncStatus;
