/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a `@wordpress/components`'s `SelectControl` with margin-bottom removed.
 *
 * If you provide `className` via props,
 * it will be added to the container div's `className`,
 * so that you can further control its style.
 *
 * @param {*} props
 */
const AppSelectControl = ( props ) => {
	const { className, ...rest } = props;

	return (
		<div className={ classNames( 'app-select-control', className ) }>
			<SelectControl { ...rest } />
		</div>
	);
};

export default AppSelectControl;
