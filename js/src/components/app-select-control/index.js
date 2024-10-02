/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
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

	// Maintain refs to prevent rerender
	const onChangeRef = useRef();
	const valueRef = useRef();
	const autoSelectFirstOptionRef = useRef();
	const optionsRef = useRef();

	// Update refs if any of the dependencies change
	useEffect( () => {
		onChangeRef.current = onChange;
		valueRef.current = value;
		autoSelectFirstOptionRef.current = autoSelectFirstOption;
		optionsRef.current = options;
	}, [ onChange, value, autoSelectFirstOption, options ] );

	useEffect( () => {
		if (
			autoSelectFirstOptionRef.current &&
			optionsRef.current?.length > 0 &&
			valueRef.current === undefined
		) {
			onChangeRef.current( optionsRef.current[ 0 ].value );
		}
	}, [] );

	let selectProps = {
		options,
		value,
		onChange,
		...rest,
	};

	const hasSingleValueStyle = autoSelectFirstOption && options?.length === 1;
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
