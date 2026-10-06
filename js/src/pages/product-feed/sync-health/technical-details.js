/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, ExternalLink, Notice, Spinner } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useMemo, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import useDataViewsScript from '~/hooks/useDataViewsScript';
import CollapsibleCard from './collapsible-card';
import DetailGrid from './detail-grid';
import { useSyncHealth, useSyncHealthJobs } from './useSyncHealth';
import {
	JOB_STATE,
	formatCount,
	formatDateTime,
	getScheduledActionsUrl,
	timeAgo,
} from './utils';
import './technical-details.scss';

/**
 * A count that links to the matching Action Scheduler list, or a plain zero.
 *
 * @param {Object} props
 * @param {number} props.count
 * @param {string} props.search Hook prefix.
 * @param {string} props.status Action Scheduler status.
 */
const ActionCount = ( { count, search, status } ) =>
	count > 0 ? (
		<ExternalLink href={ getScheduledActionsUrl( search, status ) }>
			{ formatCount( count ) }
		</ExternalLink>
	) : (
		formatCount( 0 )
	);

/**
 * Columns for the background jobs DataViews table.
 */
const JOB_FIELDS = [
	{
		id: 'name',
		label: __( 'Job', 'google-listings-and-ads' ),
		getValue: ( { item } ) => item.name,
		enableHiding: false,
	},
	...[
		[ 'pending', __( 'Waiting', 'google-listings-and-ads' ), 'pending' ],
		[
			'running',
			__( 'Running', 'google-listings-and-ads' ),
			'in-progress',
		],
		[
			'failed_24h',
			__( 'Failed (24h)', 'google-listings-and-ads' ),
			'failed',
		],
	].map( ( [ id, label, status ] ) => ( {
		id,
		label,
		type: 'integer',
		render: ( { item } ) => (
			<ActionCount
				count={ item[ id ] }
				search={ `gla/jobs/${ item.name }/` }
				status={ status }
			/>
		),
	} ) ),
	{
		id: 'last_completed_at',
		label: __( 'Last completed', 'google-listings-and-ads' ),
		type: 'integer',
		render: ( { item } ) => (
			<time title={ formatDateTime( item.last_completed_at ) }>
				{ timeAgo( item.last_completed_at, '–' ) }
			</time>
		),
	},
	{
		id: 'state',
		label: __( 'State', 'google-listings-and-ads' ),
		render: ( { item } ) => {
			const state = JOB_STATE[ item.state ] || {
				intent: 'default',
				label: item.state,
			};
			return <Badge intent={ state.intent }>{ state.label }</Badge>;
		},
	},
];

const DEFAULT_JOBS_VIEW = {
	type: 'table',
	page: 1,
	perPage: 20,
	fields: JOB_FIELDS.map( ( field ) => field.id ),
	layout: { density: 'compact' },
};

/**
 * Per-job Action Scheduler counts, as a DataViews table.
 *
 * @param {Object} props
 * @param {Array} props.jobs Jobs from the sync-health/jobs response.
 */
const JobsTable = ( { jobs } ) => {
	const { DataViews, filterSortAndPaginate } = window.wp.dataviews;
	const [ view, setView ] = useState( DEFAULT_JOBS_VIEW );

	const { data, paginationInfo } = useMemo(
		() => filterSortAndPaginate( jobs, view, JOB_FIELDS ),
		[ jobs, view, filterSortAndPaginate ]
	);

	return (
		<div className="gla-sync-technical__jobs">
			<DataViews
				getItemId={ ( item ) => item.name }
				fields={ JOB_FIELDS }
				data={ data }
				view={ view }
				onChangeView={ setView }
				paginationInfo={ paginationInfo }
				defaultLayouts={ { table: {} } }
				search={ false }
				empty={
					<p>
						{ __(
							'No background jobs ran in the last 24 hours.',
							'google-listings-and-ads'
						) }
					</p>
				}
			/>
		</div>
	);
};

/**
 * Copies the summary and job data as JSON for support tickets.
 *
 * @param {Object} props
 * @param {Object} props.summary
 * @param {Object} [props.jobs]
 */
const CopyDiagnosticsButton = ( { summary, jobs } ) => {
	const [ copied, setCopied ] = useState( false );
	const ref = useCopyToClipboard(
		() => JSON.stringify( { summary, jobs }, null, 2 ),
		() => {
			setCopied( true );
			setTimeout( () => setCopied( false ), 2000 );
		}
	);

	return (
		<Button variant="secondary" ref={ ref }>
			{ copied
				? __( 'Copied', 'google-listings-and-ads' )
				: __( 'Copy diagnostics', 'google-listings-and-ads' ) }
		</Button>
	);
};

const RATE_MODE_LABEL = {
	automatic: __(
		'From WooCommerce shipping zones',
		'google-listings-and-ads'
	),
	flat: __( 'Flat rate', 'google-listings-and-ads' ),
	manual: __( 'Set in Merchant Center', 'google-listings-and-ads' ),
};

const yesNo = ( value ) =>
	value
		? __( 'Yes', 'google-listings-and-ads' )
		: __( 'No', 'google-listings-and-ads' );

/**
 * Store-level facts support asks for first: account, connection, URL claim, shipping mode.
 *
 * @param {Object} props
 * @param {Object} props.pillars Pillars from the sync-health response.
 */
const StoreDetails = ( { pillars } ) => {
	const { connection, products, shipping } = pillars;
	const urlLabel = {
		yes: __( 'Matches', 'google-listings-and-ads' ),
		no: __( 'Does not match: syncing is off', 'google-listings-and-ads' ),
	};

	const rows = [
		[
			__( 'Merchant Center ID', 'google-listings-and-ads' ),
			connection.merchant_id || '–',
		],
		[
			__( 'Google account connected', 'google-listings-and-ads' ),
			yesNo(
				connection.google_connected && connection.mc_setup_complete
			),
		],
		[
			__( 'WordPress.com connection', 'google-listings-and-ads' ),
			yesNo( connection.jetpack_connected ),
		],
		[
			__( 'Store URL vs claimed URL', 'google-listings-and-ads' ),
			urlLabel[ connection.url_matches ] ||
				__( 'Not checked yet', 'google-listings-and-ads' ),
		],
		[
			__( 'Paused after a rejected request', 'google-listings-and-ads' ),
			connection.circuit_breaker_open
				? sprintf(
						// translators: %s: relative time, e.g. "in 20 minutes".
						__( 'Yes, retries %s', 'google-listings-and-ads' ),
						timeAgo( connection.circuit_breaker_retry_at, '' )
				  )
				: __( 'No', 'google-listings-and-ads' ),
		],
		[
			__( 'Products ready to sync', 'google-listings-and-ads' ),
			products.syncable === null ? '–' : formatCount( products.syncable ),
		],
		[
			__( 'Overdue for resubmit (25+ days)', 'google-listings-and-ads' ),
			formatCount( products.overdue_resubmit ),
		],
		[
			__( 'Shipping rates', 'google-listings-and-ads' ),
			RATE_MODE_LABEL[ shipping.rate_mode ] || '–',
		],
	];

	if ( shipping.last_failure ) {
		rows.push( [
			__( 'Last shipping error', 'google-listings-and-ads' ),
			`${ formatDateTime( shipping.last_failure.failed_at ) }: ${
				shipping.last_failure.message
			}`,
		] );
	}

	return <DetailGrid rows={ rows } />;
};

/**
 * Body of the technical section; mounted only while the card is open, so its
 * requests only run when someone looks.
 */
const TechnicalBody = () => {
	const { data: summary } = useSyncHealth();
	const { data, error, loading } = useSyncHealthJobs( true );
	const dataViewsStatus = useDataViewsScript();

	if ( ! summary ) {
		return <Spinner />;
	}

	const { queue } = summary;

	return (
		<div className="gla-sync-technical">
			<StoreDetails pillars={ summary.pillars } />

			<div className="gla-sync-technical__meta">
				<h3 className="gla-sync-technical__subheading">
					{ __( 'Background jobs', 'google-listings-and-ads' ) }
				</h3>
				<p>
					{ sprintf(
						// translators: 1: waiting jobs, 2: running jobs, 3: failed jobs in 24 hours, 4: relative time.
						__(
							'%1$s waiting, %2$s running, %3$s failed in the last 24 hours. Last job finished %4$s.',
							'google-listings-and-ads'
						),
						formatCount( queue.pending ),
						formatCount( queue.running ),
						formatCount( queue.failed_24h ),
						timeAgo( queue.last_completed_at )
					) }
				</p>
				<CopyDiagnosticsButton summary={ summary } jobs={ data } />
			</div>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error.message ||
						__(
							'Could not load background jobs.',
							'google-listings-and-ads'
						) }
				</Notice>
			) }

			{ ! data && loading && <Spinner /> }

			{ data && ! data.available && (
				<p>
					{ __(
						'Job counts are only available when Action Scheduler uses its database tables.',
						'google-listings-and-ads'
					) }
				</p>
			) }

			{ data?.available && dataViewsStatus === 'loading' && <Spinner /> }

			{ data?.available && dataViewsStatus === 'failed' && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'Could not load the jobs table.',
						'google-listings-and-ads'
					) }
				</Notice>
			) }

			{ data?.available && dataViewsStatus === 'ready' && (
				<JobsTable jobs={ data.jobs } />
			) }

			<p className="gla-sync-technical__footnote">
				<ExternalLink
					href={ getScheduledActionsUrl( 'gla/jobs/', undefined ) }
				>
					{ __(
						'View all Google for WooCommerce scheduled actions',
						'google-listings-and-ads'
					) }
				</ExternalLink>
			</p>
		</div>
	);
};

export const TECHNICAL_DETAILS_ID = 'gla-sync-technical-details';

/**
 * "Advanced troubleshooting": collapsed-by-default section at the bottom of Product Feed: store
 * connection details, background job counts, and a diagnostics export.
 *
 * @param {Object} props
 * @param {boolean} props.open Whether the section is expanded.
 * @param {Function} props.onOpenChange Called with the new open state.
 */
const TechnicalDetails = ( { open, onOpenChange } ) => (
	<CollapsibleCard
		id={ TECHNICAL_DETAILS_ID }
		title={ __( 'Advanced troubleshooting', 'google-listings-and-ads' ) }
		description={ __(
			'Connection details, background jobs and diagnostics for troubleshooting',
			'google-listings-and-ads'
		) }
		open={ open }
		onOpenChange={ onOpenChange }
	>
		<TechnicalBody />
	</CollapsibleCard>
);

export default TechnicalDetails;
