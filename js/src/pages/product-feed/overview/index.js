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
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import AppSpinner from '~/components/app-spinner';
import ProductStates from './product-states';
import StatusList from './status-list';
import {
	requestReviewRefresh,
	useProductJourney,
	useSyncHealth,
} from '../sync-health/useSyncHealth';
import {
	describeReason,
	formatCount,
	formatDateTime,
	timeAgo,
} from '../sync-health/utils';
import './index.scss';

/**
 * Sync badge per overall state. It answers only "is data reaching Google?";
 * product and account problems are listed in the body, not summarized here.
 */
const SYNC_STATE = {
	healthy: {
		intent: 'success',
		label: __( 'Synced', 'google-listings-and-ads' ),
		text: () =>
			__(
				'All changes have been sent to Google',
				'google-listings-and-ads'
			),
	},
	syncing: {
		intent: 'info',
		label: __( 'Syncing', 'google-listings-and-ads' ),
		text: ( waiting ) =>
			waiting > 0
				? sprintf(
						// translators: %s: number of products.
						_n(
							'Sending %s product change to Google',
							'Sending %s product changes to Google',
							waiting,
							'google-listings-and-ads'
						),
						formatCount( waiting )
				  )
				: __( 'Sending changes to Google', 'google-listings-and-ads' ),
	},
	attention: {
		intent: 'warning',
		label: __( 'Sync issue', 'google-listings-and-ads' ),
		text: () =>
			__(
				'Some changes are not reaching Google',
				'google-listings-and-ads'
			),
	},
	paused: {
		intent: 'warning',
		label: __( 'Sync paused', 'google-listings-and-ads' ),
		text: () =>
			__(
				'Nothing is being sent to Google right now',
				'google-listings-and-ads'
			),
	},
	disconnected: {
		intent: 'error',
		label: __( 'Not connected', 'google-listings-and-ads' ),
		text: () =>
			__(
				'Connect your Google account to start syncing',
				'google-listings-and-ads'
			),
	},
};

/**
 * Product Feed overview: is sync working, how many products are live, what to fix.
 *
 * Replaces the five equal summary numbers with one sync verdict, how many products
 * are live on Google, and the states that have products, each with a count.
 *
 * @param {Object} props
 * @param {string} [props.journey] Segment the product table is filtered to.
 * @param {Function} props.onJourneyChange Called with a segment, or '' to clear.
 * @param {Function} props.onShowTechnicalDetails Opens the technical details section.
 */
const ProductOverview = ( {
	journey,
	onJourneyChange,
	onShowTechnicalDetails,
} ) => {
	const health = useSyncHealth();
	const summary = health.data;
	const isSyncing =
		summary?.state === 'syncing' || summary?.pillars.review.loading;
	const journeyState = useProductJourney( isSyncing );
	const counts = journeyState.data;

	const syncState = SYNC_STATE[ summary?.state ] || null;
	const verdict = summary?.reasons?.length
		? describeReason( summary.reasons[ 0 ] ).text
		: null;

	const liveCount = counts
		? counts.segments.live + counts.segments.expiring
		: 0;

	const [ isRefreshing, setIsRefreshing ] = useState( false );
	const [ refreshNote, setRefreshNote ] = useState( '' );

	// Refresh always re-checks sync status, and also asks for new review results.
	// The server skips the Google fetch when one is running or ran recently, so the
	// button itself is never disabled. Automatic polling never calls this.
	const refresh = () => {
		setIsRefreshing( true );
		setRefreshNote( '' );

		requestReviewRefresh()
			.then( ( result ) => {
				if ( result?.reason === 'running' ) {
					setRefreshNote(
						__(
							'Review results are already being fetched from Google.',
							'google-listings-and-ads'
						)
					);
				} else if ( result?.reason === 'recent' ) {
					setRefreshNote(
						sprintf(
							// translators: %s: relative time, e.g. "in 3 minutes".
							__(
								'Review results were fetched recently. New results can be fetched %s.',
								'google-listings-and-ads'
							),
							timeAgo( result.available_at, '' )
						)
					);
				}
			} )
			.catch( () =>
				setRefreshNote(
					__(
						'Could not fetch review results from Google.',
						'google-listings-and-ads'
					)
				)
			)
			.finally( () => {
				health.refresh();
				journeyState.refresh();
				setIsRefreshing( false );
			} );
	};

	return (
		<Card className="gla-product-overview">
			<CardHeader className="gla-product-overview__header">
				<div className="gla-product-overview__verdict">
					<h2 className="gla-product-overview__title">
						{ __( 'Overview', 'google-listings-and-ads' ) }
					</h2>
					{ syncState && (
						<>
							<Badge intent={ syncState.intent }>
								{ syncState.label }
							</Badge>
							<span className="gla-product-overview__muted">
								{ syncState.text(
									counts?.segments.waiting || 0
								) }
							</span>
						</>
					) }
				</div>
				<div className="gla-product-overview__refresh">
					{ summary && (
						<span
							className="gla-product-overview__muted"
							title={ formatDateTime( summary.generated_at ) }
						>
							{ sprintf(
								// translators: %s: relative time, e.g. "2 minutes ago".
								__( 'Checked %s', 'google-listings-and-ads' ),
								timeAgo( summary.generated_at )
							) }
						</span>
					) }
					<Button
						variant="tertiary"
						onClick={ refresh }
						isBusy={ isRefreshing || health.loading }
						describedBy={ __(
							'Checks sync status and fetches new review results from Google.',
							'google-listings-and-ads'
						) }
					>
						{ __( 'Refresh', 'google-listings-and-ads' ) }
					</Button>
				</div>
				{ refreshNote && (
					<p
						className="gla-product-overview__refresh-note"
						role="status"
					>
						{ refreshNote }
					</p>
				) }
			</CardHeader>

			<CardBody className="gla-product-overview__body">
				{ ( health.error || journeyState.error ) && (
					<Notice status="error" isDismissible={ false }>
						{ ( health.error || journeyState.error ).message ||
							__(
								'Could not load the overview.',
								'google-listings-and-ads'
							) }
					</Notice>
				) }

				{ ! counts && journeyState.loading && <AppSpinner /> }

				{ counts && (
					<>
						<p className="gla-product-overview__headline">
							{ sprintf(
								// translators: 1: products live on Google, 2: all products.
								__(
									'%1$s of %2$s products are live on Google',
									'google-listings-and-ads'
								),
								formatCount( liveCount ),
								formatCount( counts.total )
							) }
						</p>
						{ verdict && (
							<p className="gla-product-overview__muted">
								{ verdict }
							</p>
						) }

						<ProductStates
							segments={ counts.segments }
							selected={ journey }
							onSelect={ onJourneyChange }
						/>
					</>
				) }
			</CardBody>

			{ summary && (
				<CardFooter className="gla-product-overview__footer">
					<StatusList
						pillars={ summary.pillars }
						hasProducts={ ( counts?.total || 0 ) > 0 }
						onShowDetails={ onShowTechnicalDetails }
					/>
				</CardFooter>
			) }
		</Card>
	);
};

export default ProductOverview;
