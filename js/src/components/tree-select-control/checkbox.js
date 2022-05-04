/**
 * External dependencies
 */
import { Icon, check } from '@wordpress/icons';

/**
 * @typedef {import('./index').RepositoryOption} RepositoryOption
 */

/**
 * Renders a custom Checkbox
 *
 * @param {Object} props Component properties
 * @param {RepositoryOption} props.option Option for the checkbox
 * @param {number} props.index The position in the tree
 * @param {string} props.className The className for the component
 * @param {boolean} props.checked Defines if the checkbox is checked
 * @return {JSX.Element|null} The Checkbox component
 */
const Checkbox = ( { option, index, checked, className, ...props } ) => {
	if ( ! option ) return null;

	return (
		<div className={ className }>
			<div className="components-base-control__field">
				<span className="components-checkbox-control__input-container">
					<input
						ref={ option.ref }
						id={ `inspector-checkbox-control-${
							option.key ?? option.value
						}` }
						data-index={ index }
						className="components-checkbox-control__input"
						type="checkbox"
						tabIndex="-1"
						value={ option.value }
						checked={ checked }
						{ ...props }
					/>
					{ checked && (
						<Icon
							icon={ check }
							role="presentation"
							className="components-checkbox-control__checked"
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
