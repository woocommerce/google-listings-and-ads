/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	Notice,
} from '@wordpress/components';
import { createInterpolateElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import Text from '~/components/app-text';
import AppSpinner from '~/components/app-spinner';
import HorizontalStackedBar from '~/components/horizontal-stacked-bar';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import { recordGlaEvent } from '~/utils/tracks';
import { ACCOUNT_ISSUES_ID } from '../constants';
import DetailGrid from '../sync-health/detail-grid';
import TechnicalDetails from '../sync-health/technical-details';
import When from '../sync-health/when';
import { PRODUCT_STATUSES } from '../sync-health/product-status';
import { describeReason, formatCount } from '../sync-health/utils';
import './index.scss';

/**
 * Reason severities that mean data is not reaching Google, which is what puts
 * the overall sync state in `failed`. As on the server, reasons about Google's
 * review results never do, whatever their severity.
 */
const FAILURE_SEVERITIES = [ 'error', 'paused', 'disconnected' ];

/**
 * Describes what is being sent to Google right now.
 *
 * @param {Object} [syncing] `syncing` from the sync-health response.
 * @return {string} Description.
 */
const describeSyncing = ( syncing = {} ) => {
	if ( syncing.full_sync ) {
		return __(
			'Sending your full catalog to Google',
			'google-listings-and-ads'
		);
	}

	if ( syncing.products > 0 ) {
		return sprintf(
			// translators: %s: number of product changes.
			_n(
				'Sending %s product change to Google',
				'Sending %s product changes to Google',
				syncing.products,
				'google-listings-and-ads'
			),
			formatCount( syncing.products )
		);
	}

	if ( syncing.shipping ) {
		return __(
			'Sending shipping data to Google',
			'google-listings-and-ads'
		);
	}

	return __( 'Sending changes to Google', 'google-listings-and-ads' );
};

/**
 * Describes why sync failed, from the first reason that stops data reaching Google.
 *
 * @param {Array} [reasons] `reasons` from the sync-health response.
 * @return {string} Description.
 */
const describeFailure = ( reasons = [] ) => {
	const reason =
		reasons.find(
			( { severity, pillar } ) =>
				FAILURE_SEVERITIES.includes( severity ) && pillar !== 'review'
		) || reasons[ 0 ];

	return reason
		? describeReason( reason ).text
		: __( 'Changes are not reaching Google', 'google-listings-and-ads' );
};

/**
 * Sync badge per overall state. It answers only "is data reaching Google?";
 * product and account problems never change it.
 */
const SYNC_STATE = {
	synced: {
		intent: 'success',
		label: __( 'Synced', 'google-listings-and-ads' ),
		describe: () =>
			__(
				'All changes have been sent to Google',
				'google-listings-and-ads'
			),
	},
	syncing: {
		intent: 'info',
		label: __( 'Syncing', 'google-listings-and-ads' ),
		describe: ( summary ) => describeSyncing( summary.syncing ),
	},
	failed: {
		intent: 'error',
		label: __( 'Sync failed', 'google-listings-and-ads' ),
		describe: ( summary ) => describeFailure( summary.reasons ),
	},
};

/**
 * When each kind of sync last finished.
 *
 * @param {Object} props
 * @param {Object} props.pillars Pillars from the sync-health response.
 */
const SyncTimes = ( { pillars } ) => {
	const { products, shipping } = pillars;
	const fullSync = products.full_sync || {};

	let fullSyncText = <When timestamp={ fullSync.last_completed_at } />;
	if ( fullSync.in_progress ) {
		fullSyncText = __( 'In progress', 'google-listings-and-ads' );
	} else if ( fullSync.interrupted ) {
		fullSyncText = __(
			'Stopped before it finished',
			'google-listings-and-ads'
		);
	}

	const rows = [
		[
			__( 'Last full catalog sync', 'google-listings-and-ads' ),
			fullSyncText,
		],
		[
			__( 'Last product sync', 'google-listings-and-ads' ),
			<When key="push" timestamp={ products.last_push_at } />,
		],
	];

	if ( shipping.syncable ) {
		rows.push( [
			__( 'Last shipping sync', 'google-listings-and-ads' ),
			<When key="shipping" timestamp={ shipping.last_success_at } />,
		] );
	}

	return <DetailGrid rows={ rows } />;
};

/**
 * Scrolls to the account issues card and moves focus to it.
 */
const showAccountIssues = () => {
	const card = document.getElementById( ACCOUNT_ISSUES_ID );
	card?.scrollIntoView?.( { behavior: 'smooth' } );
	card?.focus?.( { preventScroll: true } );
};

/**
 * Error notice shown while the Merchant Center account is disapproved, since
 * that blocks every product regardless of its own status. The account issues
 * card always renders for a disapproved account, so the link has a target.
 */
const AccountBlockedNotice = () => {
	const { data } = useAppSelectDispatch( 'getMCReviewRequest' );

	if ( data?.status !== 'DISAPPROVED' ) {
		return null;
	}

	return (
		<Notice status="error" isDismissible={ false }>
			<p>
				{ __(
					'No products can show on Google until your Merchant Center account issues are fixed.',
					'google-listings-and-ads'
				) }
			</p>
			<Button variant="link" onClick={ showAccountIssues }>
				{ __( 'View account issues', 'google-listings-and-ads' ) }
			</Button>
		</Notice>
	);
};

/**
 * When review results were last fetched from Google, and whether new ones are
 * being fetched now.
 *
 * @param {Object} props
 * @param {Object} props.review Review pillar from the sync-health response.
 */
const ReviewDataAge = ( { review } ) => {
	const age = review.data_at ? <When timestamp={ review.data_at } /> : null;

	if ( review.loading ) {
		return age
			? createInterpolateElement(
					// translators: <age/>: relative time, e.g. "4 minutes ago".
					__(
						'Updating… (last updated <age/>)',
						'google-listings-and-ads'
					),
					{ age }
			  )
			: __( 'Updating…', 'google-listings-and-ads' );
	}

	return age
		? createInterpolateElement(
				// translators: <age/>: relative time, e.g. "4 minutes ago".
				__( 'Updated <age/>', 'google-listings-and-ads' ),
				{ age }
		  )
		: __( 'Not checked yet', 'google-listings-and-ads' );
};

/**
 * How many products Google approved, with the age of the review data, and a bar
 * of products by status.
 *
 * @param {Object} props
 * @param {Object} props.counts Product-journey response.
 * @param {Object} [props.review] Review pillar from the sync-health response.
 */
const ProductStatusBar = ( { counts, review } ) => {
	const { total, segments } = counts;

	const title = (
		<>
			<span className="gla-product-overview__headline">
				{ sprintf(
					// translators: 1: products approved by Google, 2: all products.
					_n(
						'%1$s of %2$s product approved by Google',
						'%1$s of %2$s products approved by Google',
						total,
						'google-listings-and-ads'
					),
					formatCount( segments.approved ),
					formatCount( total )
				) }
			</span>
			{ review && (
				<span className="gla-product-overview__muted">
					<ReviewDataAge review={ review } />
				</span>
			) }
		</>
	);

	// The bar divides by the sum of its segments, so it has nothing to show
	// without products.
	if ( ! total ) {
		return <p className="gla-product-overview__title-row">{ title }</p>;
	}

	// Approved is always shown, even at zero; the other statuses only when they
	// have products.
	const barSegments = PRODUCT_STATUSES.filter(
		( { key } ) => key === 'approved' || segments[ key ] > 0
	).map( ( { key, label, color } ) => ( {
		id: key,
		label,
		color,
		value: segments[ key ] || 0,
	} ) );

	return (
		<HorizontalStackedBar
			className="gla-product-overview__bar"
			title={ title }
			segments={ barSegments }
		/>
	);
};

/**
 * Triggered when the Refresh button on the Product Feed overview is clicked.
 * Not fired by the automatic refresh when the page is visited.
 *
 * @event gla_product_feed_refresh_click
 */

/**
 * Triggered when the "View diagnostics" button on the Product Feed overview is
 * clicked, opening Advanced troubleshooting.
 *
 * @event gla_product_feed_diagnostics_click
 */

/**
 * Product Feed overview: is data reaching Google, how many products Google
 * approved, and what is blocking the rest.
 *
 * @fires gla_product_feed_refresh_click
 * @fires gla_product_feed_diagnostics_click
 *
 * @param {Object} props
 * @param {Object} [props.summary] Sync health summary, when loaded.
 * @param {Object} [props.counts] Product status counts, when loaded.
 * @param {Object} [props.error] Error from loading either of them.
 * @param {boolean} props.isRefreshing Whether a refresh is running.
 * @param {string} [props.refreshNote] Why review results weren't fetched, if they weren't.
 * @param {Function} props.onRefresh Refreshes the overview.
 */
const ProductOverview = ( {
	summary,
	counts,
	error,
	isRefreshing,
	refreshNote,
	onRefresh,
} ) => {
	const [ isDiagnosticsOpen, setDiagnosticsOpen ] = useState( false );
	const syncState = SYNC_STATE[ summary?.state ];
	const isBusy = isRefreshing || !! summary?.pillars.review.loading;

	const handleRefreshClick = () => {
		recordGlaEvent( 'gla_product_feed_refresh_click' );
		onRefresh();
	};

	const handleDiagnosticsClick = () => {
		recordGlaEvent( 'gla_product_feed_diagnostics_click' );
		setDiagnosticsOpen( true );
	};

	return (
		<Card className="gla-product-overview">
			<CardHeader className="gla-product-overview__header">
				<div className="gla-product-overview__verdict">
					<Text variant="title-small" as="h2">
						{ __( 'Overview', 'google-listings-and-ads' ) }
					</Text>
					{ syncState && (
						<>
							<Badge intent={ syncState.intent }>
								{ syncState.label }
							</Badge>
							<span className="gla-product-overview__muted">
								{ syncState.describe( summary ) }
							</span>
						</>
					) }
				</div>
				<div className="gla-product-overview__refresh">
					{ summary && (
						<span className="gla-product-overview__muted">
							{ createInterpolateElement(
								// translators: <age/>: relative time, e.g. "2 minutes ago".
								__(
									'Checked <age/>',
									'google-listings-and-ads'
								),
								{
									age: (
										<When
											timestamp={ summary.generated_at }
										/>
									),
								}
							) }
						</span>
					) }
					<Button
						variant="tertiary"
						onClick={ handleRefreshClick }
						// Busy while refreshing, and while review results are being
						// fetched from Google in the background. Never disabled.
						isBusy={ isBusy }
						// Core Button only styles `isBusy`, so tell assistive tech too.
						aria-busy={ isBusy }
						describedBy={ __(
							'Checks sync status and fetches new review results from Google.',
							'google-listings-and-ads'
						) }
					>
						{ __( 'Refresh', 'google-listings-and-ads' ) }
					</Button>
				</div>
				{ /* Always rendered, so screen readers announce changes to it. */ }
				<p className="gla-product-overview__refresh-note" role="status">
					{ refreshNote }
				</p>
				{ summary && (
					<div className="gla-product-overview__sync-times">
						<SyncTimes pillars={ summary.pillars } />
					</div>
				) }
			</CardHeader>

			<CardBody className="gla-product-overview__body">
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error.message ||
							__(
								'Could not load the overview.',
								'google-listings-and-ads'
							) }
					</Notice>
				) }

				<AccountBlockedNotice />

				{ ! counts && isRefreshing && <AppSpinner /> }

				{ counts && (
					<ProductStatusBar
						counts={ counts }
						review={ summary?.pillars.review }
					/>
				) }
			</CardBody>

			<CardFooter className="gla-product-overview__footer">
				<Button variant="secondary" onClick={ handleDiagnosticsClick }>
					{ __( 'View diagnostics', 'google-listings-and-ads' ) }
				</Button>
			</CardFooter>

			{ isDiagnosticsOpen && (
				<TechnicalDetails
					summary={ summary }
					onRequestClose={ () => setDiagnosticsOpen( false ) }
				/>
			) }
		</Card>
	);
};

export default ProductOverview;
