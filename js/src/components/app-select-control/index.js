/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import classNames from 'classnames';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a `@wordpress/components`'s `SelectControl` with margin-bottom removed.
 *
 * @param {*} props The component props.
 * @param {Array} props.options Array of options for the select dropdown. Each option should be an object containing `label` and `value` properties.
 * @param {string} [props.className] Additional classname to further control the style of the component.
 * @param {Function} [props.onChange=noop] Callback function triggered when the selected value changes. Receives the new value as an argument.
 * @param {string} [props.value] The selected value. If no value is defined, the first option is selected and `onChange` is triggered when `autoSelectFirstOption` is true.
 * @param {boolean} [props.autoSelectFirstOption=false] If true, automatically triggers the onChange callback with the first option as value when no value is provided. If only one option is available, the select is also disabled to prevent user interaction.
 * @param {*} [props.rest] Additional props passed to the `SelectControl` component.
 */
const AppSelectControl = ( props ) => {
	const {
		options,
		className,
		onChange = noop,
		value,
		autoSelectFirstOption = false,
		...rest
	} = props;
	const [ hasTriggerredOnChangeOnMount, setHasTriggerredOnChangeOnMount ] =
		useState( false );
	const hasSingleValueStyle = autoSelectFirstOption && options?.length === 1;

	useEffect( () => {
		if ( hasTriggerredOnChangeOnMount ) {
			return;
		}

		if (
			autoSelectFirstOption &&
			options?.length > 0 &&
			value === undefined
		) {
			onChange( options[ 0 ].value );
			setHasTriggerredOnChangeOnMount( true );
		}
	}, [
		autoSelectFirstOption,
		onChange,
		options,
		value,
		hasTriggerredOnChangeOnMount,
	] );

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
