/**
 * External dependencies
 */
import { ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * A ToggleControl that is meant to be used standalone, without label and other texts.
 *
 * This ToggleControl has its margins removed.
 *
 * @param {Object} props ToggleControl props.
 */
const AppStandaloneToggleControl = ( props ) => {
	return (
		<div className="app-standalone-toggle-control">
			<ToggleControl { ...props } />
		</div>
	);
};

export default AppStandaloneToggleControl;
