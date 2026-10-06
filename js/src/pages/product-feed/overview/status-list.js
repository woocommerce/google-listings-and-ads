/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';
import { REVIEW_STATUSES } from '../constants';
import DetailGrid from '../sync-health/detail-grid';
import {
	STATUS_INTENT,
	STATUS_LABEL,
	formatDateTime,
	timeAgo,
} from '../sync-health/utils';

/**
 * Badge and plain-text label per Merchant Center account review status.
 * Descriptions come from REVIEW_STATUSES so the copy stays in one place.
 */
const ACCOUNT = {
	APPROVED: {
		intent: 'success',
		label: __( 'Approved', 'google-listings-and-ads' ),
	},
	WARNING: {
		intent: 'warning',
		label: __( 'Warning', 'google-listings-and-ads' ),
	},
	DISAPPROVED: {
		intent: 'error',
		label: __( 'Disapproved', 'google-listings-and-ads' ),
	},
	UNDER_REVIEW: {
		intent: 'info',
		label: __( 'Under review', 'google-listings-and-ads' ),
	},
	ONBOARDING: {
		intent: 'default',
		label: __( 'No products added', 'google-listings-and-ads' ),
	},
};

/**
 * Relative time with the absolute time on hover.
 *
 * @param {Object} props
 * @param {number|null} props.timestamp
 * @param {string} [props.fallback]
 */
const When = ( { timestamp, fallback } ) => (
	<time title={ formatDateTime( timestamp ) }>
		{ timeAgo( timestamp, fallback ) }
	</time>
);

/**
 * One row: label, optional badge, and a value or explanation.
 *
 * @param {Object} props
 * @param {string} props.label
 * @param {Object} [props.badge] `{ intent, label }`.
 * @param {JSX.Element|string} [props.children]
 */
const Row = ( { label, badge, children } ) => (
	<div className="gla-product-overview__status-row">
		<dt>{ label }</dt>
		<dd>
			{ badge && <Badge intent={ badge.intent }>{ badge.label }</Badge> }
			{ children && (
				<span className="gla-product-overview__status-text">
					{ children }
				</span>
			) }
		</dd>
	</div>
);

/**
 * Status badge for a sync-health pillar.
 *
 * @param {Object} pillar
 * @return {Object} `{ intent, label }`.
 */
const pillarBadge = ( pillar ) => ( {
	intent: STATUS_INTENT[ pillar.status ] || 'default',
	label: STATUS_LABEL[ pillar.status ] || pillar.status,
} );

/**
 * A value, preceded by the pillar's status badge when it isn't healthy.
 *
 * @param {Object} pillar
 * @param {JSX.Element|string} value
 * @return {JSX.Element} Value with optional badge.
 */
const withBadge = ( pillar, value ) => (
	<>
		{ pillar.status !== 'ok' && (
			<Badge intent={ pillarBadge( pillar ).intent }>
				{ pillarBadge( pillar ).label }
			</Badge>
		) }
		<span>{ value }</span>
	</>
);

/**
 * Merchant Center account review status. An issue-free account with no products
 * reads as "No products added" rather than Approved, matching AccountStatus.
 *
 * @param {Object} props
 * @param {boolean} props.hasProducts
 */
const AccountRow = ( { hasProducts } ) => {
	const { data, hasFinishedResolution } =
		useAppSelectDispatch( 'getMCReviewRequest' );

	if ( ! hasFinishedResolution || ! data?.status ) {
		return null;
	}

	const key =
		data.status === 'APPROVED' && ! hasProducts
			? 'ONBOARDING'
			: data.status;
	const account = ACCOUNT[ key ];

	if ( ! account ) {
		return null;
	}

	return (
		<Row
			label={ __( 'Merchant Center account', 'google-listings-and-ads' ) }
			badge={ account }
		>
			{ REVIEW_STATUSES[ key ]?.statusDescription }
		</Row>
	);
};

/**
 * Connection and account review as badge rows, then when each kind of sync last
 * ran as a compact grid.
 *
 * @param {Object} props
 * @param {Object} props.pillars Pillars from the sync-health response.
 * @param {boolean} props.hasProducts Whether the store has any products.
 * @param {Function} props.onShowDetails Opens the technical details section.
 */
const StatusList = ( { pillars, hasProducts, onShowDetails } ) => {
	const { connection, products, shipping, review } = pillars;
	const fullSync = products.full_sync;

	let fullSyncText = <When timestamp={ fullSync.last_completed_at } />;
	if ( fullSync.in_progress ) {
		fullSyncText = __( 'In progress', 'google-listings-and-ads' );
	} else if ( fullSync.interrupted ) {
		fullSyncText = __(
			'Stopped before it finished',
			'google-listings-and-ads'
		);
	}

	let reviewText = <When timestamp={ review.data_at } />;
	if ( review.error ) {
		reviewText = review.error;
	} else if ( review.loading ) {
		reviewText = __( 'Reading from Google…', 'google-listings-and-ads' );
	} else if ( ! review.data_at ) {
		reviewText = __( 'Not read yet', 'google-listings-and-ads' );
	}

	let shippingText = <When timestamp={ shipping.last_success_at } />;
	if ( ! shipping.syncable ) {
		shippingText = __(
			'Managed in Merchant Center',
			'google-listings-and-ads'
		);
	} else if ( shipping.status === 'error' && shipping.last_failure ) {
		shippingText = sprintf(
			// translators: 1: relative time, 2: error message.
			__( 'Failed %1$s: %2$s', 'google-listings-and-ads' ),
			timeAgo( shipping.last_failure.failed_at ),
			shipping.last_failure.message
		);
	}

	return (
		<div className="gla-product-overview__status">
			<dl className="gla-product-overview__status-list">
				<Row
					label={ __(
						'Google connection',
						'google-listings-and-ads'
					) }
					badge={ pillarBadge( connection ) }
				/>
				<AccountRow hasProducts={ hasProducts } />
			</dl>
			<DetailGrid
				rows={ [
					[
						__( 'Last product update', 'google-listings-and-ads' ),
						<When key="push" timestamp={ products.last_push_at } />,
					],
					[
						__( 'Last full sync', 'google-listings-and-ads' ),
						fullSyncText,
					],
					[
						__( 'Last review results', 'google-listings-and-ads' ),
						withBadge( review, reviewText ),
					],
					[
						__( 'Last shipping sync', 'google-listings-and-ads' ),
						withBadge( shipping, shippingText ),
					],
				] }
			/>
			<Button variant="link" onClick={ onShowDetails }>
				{ __(
					'View advanced troubleshooting',
					'google-listings-and-ads'
				) }
			</Button>
		</div>
	);
};

export default StatusList;
