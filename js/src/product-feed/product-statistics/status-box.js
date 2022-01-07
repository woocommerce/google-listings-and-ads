/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { format as formatDate } from '@wordpress/date';
import { Flex, FlexItem } from '@wordpress/components';
import GridiconCheckmarkCircle from 'gridicons/dist/checkmark-circle';
import GridiconSync from 'gridicons/dist/sync';

/**
 * Internal dependencies
 */
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import useMCIssuesTotals from '.~/hooks/useMCIssuesTotals';
import { glaData } from '.~/constants';

function getUnsolvedStatusText( totalUnsolvedIssues ) {
	if ( ! Number.isInteger( totalUnsolvedIssues ) ) {
		return '';
	}

	if ( totalUnsolvedIssues === 0 ) {
		return __( 'No issues to resolve 🎉', 'google-listings-and-ads' );
	}

	return sprintf(
		// translators: %d: number of unsolved Merchant Center issues, with minimum value of 1.
		_n(
			'%d issue to resolve',
			'%d issues to resolve',
			totalUnsolvedIssues,
			'google-listings-and-ads'
		),
		totalUnsolvedIssues
	);
}

function getSyncResult( {
	scheduled_sync: scheduledSync,
	statistics,
	timestamp,
} ) {
	if ( scheduledSync !== 0 ) {
		return {
			Icon: GridiconSync,
			highlight: __( 'Sync in progress', 'google-listings-and-ads' ),
			status: null,
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
		Icon: GridiconCheckmarkCircle,
		highlight: __(
			'Automatically synced to Google',
			'google-listings-and-ads'
		),
		status: sprintf(
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

function FeedStatus() {
	const { total } = useMCIssuesTotals();
	const status = getUnsolvedStatusText( total );

	return (
		<Flex justify="normal" gap={ 1 }>
			<FlexItem>
				{ __( 'Feed setup:', 'google-listings-and-ads' ) }
			</FlexItem>
			<FlexItem>
				<GridiconCheckmarkCircle />
				{ __(
					'Standard free listings setup completed',
					'google-listings-and-ads'
				) }
			</FlexItem>
			<FlexItem>{ status }</FlexItem>
		</Flex>
	);
}

function SyncStatus() {
	const { data } = useAppSelectDispatch( 'getMCProductStatistics' );
	const { Icon, highlight, status } = getSyncResult( data );

	return (
		<Flex justify="normal" gap={ 1 }>
			<FlexItem>
				{ __( 'Sync with Google:', 'google-listings-and-ads' ) }
			</FlexItem>
			<FlexItem>
				<Icon />
				{ highlight }
			</FlexItem>
			<FlexItem>{ status }</FlexItem>
		</Flex>
	);
}

export default function StatusBox() {
	return (
		<>
			<FeedStatus />
			<SyncStatus />
		</>
	);
}
