/**
 * External dependencies
 */
import { Button, Tooltip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import {
	SEGMENT_HELP,
	SEGMENT_INTENT,
	SEGMENT_LABEL,
	SEGMENT_ORDER,
} from './journey';
import { formatCount } from '../sync-health/utils';

/**
 * Approved products first (always shown, even at zero), then the other states
 * that have products, as a row of badges with counts, problems first.
 * Selecting one filters the product table; selecting it again clears the filter.
 *
 * @param {Object} props
 * @param {Object} props.segments Counts keyed by segment.
 * @param {string} [props.selected] Selected segment.
 * @param {Function} props.onSelect Called with a segment, or '' to clear.
 */
const ProductStates = ( { segments, selected, onSelect } ) => {
	const states = SEGMENT_ORDER.filter(
		( segment ) => segment === 'live' || segments[ segment ] > 0
	);

	return (
		<div className="gla-product-overview__states">
			{ states.map( ( segment ) => {
				const isSelected = selected === segment;

				return (
					<Tooltip key={ segment } text={ SEGMENT_HELP[ segment ] }>
						<Button
							className="gla-product-overview__state"
							// Selected reads as an outlined button. Not aria-pressed: core Button turns
							// that into `is-pressed`, a dark fill that hides the badge and count.
							variant={ isSelected ? 'secondary' : 'tertiary' }
							aria-current={ isSelected ? 'true' : undefined }
							disabled={ ! segments[ segment ] }
							describedBy={ SEGMENT_HELP[ segment ] }
							onClick={ () =>
								onSelect( isSelected ? '' : segment )
							}
						>
							<Badge intent={ SEGMENT_INTENT[ segment ] }>
								{ SEGMENT_LABEL[ segment ] }
							</Badge>
							<span className="gla-product-overview__state-count">
								{ formatCount( segments[ segment ] ) }
							</span>
						</Button>
					</Tooltip>
				);
			} ) }
		</div>
	);
};

export default ProductStates;
