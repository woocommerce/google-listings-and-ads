/**
 * External dependencies
 */
import { Icon, check } from '@wordpress/icons';

/**
 * @typedef {import('./index').Option} Option
 */

/**
 * Renders a custom Checkbox
 *
 * @param {Object} props Component properties
 * @param {Option} props.option Option for the checkbox
 * @param {string} props.className The className for the component
 * @param {boolean} props.checked Defines if the checkbox is checked
 * @return {JSX.Element|null} The Checkbox component
 */
const Checkbox = ( { option, checked, className, ...props } ) => {
	if ( ! option ) {
		return null;
	}

	return (
		<div className={ className }>
			<div className="components-base-control__field">
				<span className="components-checkbox-control__input-container">
					<input
						checked={ checked }
						className="components-checkbox-control__input"
						id={ `inspector-checkbox-control-${
							option.key ?? option.value
						}` }
						tabIndex="-1"
						type="checkbox"
						value={ option.value }
						{ ...props }
					/>
					{ checked && (
						<Icon
							className="components-checkbox-control__checked"
							icon={ check }
							role="presentation"
						/>
					) }
				</span>
				<label
					className="components-checkbox-control__label"
					htmlFor={ `inspector-checkbox-control-${
						option.key ?? option.value
					}` }
				>
					{ option.label }
				</label>
			</div>
		</div>
	);
};

export default Checkbox;
