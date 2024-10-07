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
 * @param {Array} [props.options=[]] Array of options for the select dropdown. Each option should be an object containing `label` and `value` properties.
 * @param {string} [props.className] Additional classname to further control the style of the component.
 * @param {Function} [props.onChange=noop] Callback function triggered when the selected value changes. Receives the new value as an argument.
 * @param {string} [props.value] The currently selected value. This component should be used as a controlled component. A special case is that after mounting, when `autoSelectFirstOption` is true and `value` is undefined, it tries to call back `onChange` once to select the first option so that the `value` can be consistent with the `<select>` element's own value.
 * @param {boolean} [props.autoSelectFirstOption=false] If true, automatically triggers the onChange callback with the first option as value when no value is provided. If only one option is available, the select control is also changed to non-interactive.
 * @param {*} [props.rest] Additional props passed to the `SelectControl` component.
 */
const AppSelectControl = ( props ) => {
	const {
		options = [],
		className,
		onChange = noop,
		value,
		autoSelectFirstOption = false,
		...rest
	} = props;
	const shouldAutoSelectOnceRef = useRef( autoSelectFirstOption === true );

	useEffect( () => {
		if ( ! shouldAutoSelectOnceRef.current ) {
			return;
		}

		if ( value === undefined && options.length ) {
			shouldAutoSelectOnceRef.current = false;
			onChange( options[ 0 ].value );
		}
	}, [ value, options, onChange ] );

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
