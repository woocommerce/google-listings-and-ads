/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	EFFECTIVENESS_UNSPECIFIED,
	EFFECTIVENESS_LOW,
	EFFECTIVENESS_MEDIUM,
	EFFECTIVENESS_HIGH,
} from './constants';
import Badge from '~/components/badge';

const EFFECTIVENESS_MAP = {
	[ EFFECTIVENESS_UNSPECIFIED ]: {
		intent: 'default',
		label: __( 'Unspecified', 'google-listings-and-ads' ),
	},
	[ EFFECTIVENESS_LOW ]: {
		intent: 'error',
		label: __( 'Low', 'google-listings-and-ads' ),
	},
	[ EFFECTIVENESS_MEDIUM ]: {
		intent: 'warning',
		label: __( 'Medium', 'google-listings-and-ads' ),
	},
	[ EFFECTIVENESS_HIGH ]: {
		intent: 'success',
		label: __( 'High', 'google-listings-and-ads' ),
	},
};

/**
 * Component to display an effectiveness indicator badge based on the provided effectiveness level.
 *
 * @param {Object} props - The component props.
 * @param {string} props.effectiveness - The effectiveness level to determine the badge's intent and label.
 * @return {JSX.Element|null} A Badge component with the corresponding intent and label, or null if the effectiveness level is invalid.
 */
const EffectivenessIndicator = ( { effectiveness } ) => {
	if ( ! EFFECTIVENESS_MAP[ effectiveness ] ) {
		return null;
	}

	return (
		<Badge intent={ EFFECTIVENESS_MAP[ effectiveness ].intent }>
			{ EFFECTIVENESS_MAP[ effectiveness ].label }
		</Badge>
	);
};

export default EffectivenessIndicator;
