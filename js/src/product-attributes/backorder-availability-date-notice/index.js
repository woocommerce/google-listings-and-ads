/**
 * External dependencies
 */
import { createRoot, lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { GLA_DATE_INPUT_ID, GLA_TIME_INPUT_ID } from './constants';
import { glaProductData } from '~/constants';

const BackorderAvailabilityDateNotice = lazy( () =>
	import(
		/* webpackChunkName: "backorder-availability-date-notice" */ './backorder-availability-date-notice'
	)
);

/**
 * Backorder availability date notice on the product Inventory tab (classic editor).
 * Mounts a React component that shows/hides based on backorder selection
 * and GLA availability date field.
 */
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.querySelector(
		'.gla-backorder-availability-date-notice'
	);

	if ( ! container ) {
		return;
	}

	const glaDateEl = document.getElementById( GLA_DATE_INPUT_ID );
	const glaTimeEl = document.getElementById( GLA_TIME_INPUT_ID );

	if ( ! glaDateEl || ! glaTimeEl ) {
		return;
	}

	const tabTarget = glaProductData.glaTabTarget || 'gla_attributes';

	createRoot( container ).render(
		<Suspense>
			<BackorderAvailabilityDateNotice
				tabTarget={ tabTarget }
				glaDateEl={ glaDateEl }
				glaTimeEl={ glaTimeEl }
			/>
		</Suspense>
	);
} );
