/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
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
 * @param {boolean} [props.autoSelectFirstOption=false] Whether the automatically select the first option.
 */
const AppSelectControl = ( props ) => {
	const {
		className,
		options,
		onChange,
		value,
		autoSelectFirstOption = false,
		...rest
	} = props;
	const hasSingleValueStyle = autoSelectFirstOption && options?.length === 1;

	useEffect( () => {
		// Triggers the onChange event to set the initial value for the select
		if (
			autoSelectFirstOption &&
			options?.length > 0 &&
			value === undefined
		) {
			onChange( options[ 0 ].value );
		}
	}, [ autoSelectFirstOption, onChange, options, value ] );

	let selectProps = {
		options,
		value,
		onChange,
		...rest,
	};

	if ( hasSingleValueStyle ) {
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
				'app-select-control--has-single-value': hasSingleValueStyle,
			} ) }
		>
			<SelectControl { ...selectProps } />
		</div>
	);
};

export default AppSelectControl;
