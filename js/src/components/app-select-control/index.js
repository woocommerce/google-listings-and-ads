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
 * @param {*} props The component props.
 * @param {boolean} [props.selectSingleValue=false] Whether the select should show only one value.
 */
const AppSelectControl = ( props ) => {
	const { className, options, selectSingleValue = false, ...rest } = props;
	const showSingleValue = selectSingleValue && options?.length === 1;

	let selectProps = {
		options,
		...rest,
	};

	if ( showSingleValue ) {
		selectProps = {
			...selectProps,
			suffix: ' ',
			tabIndex: '-1',
			readOnly: true,
		};
	}

	return (
		<div
			className={ classNames( 'app-select-control', className, {
				'app-select-control--has-single-value': showSingleValue,
			} ) }
		>
			<SelectControl { ...selectProps } />
		</div>
	);
};

export default AppSelectControl;
