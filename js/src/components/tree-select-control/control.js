/**
 * External dependencies
 */
import classnames from 'classnames';
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Tags from './tags';

/**
 * The Control Component renders a search input and also the Tags. It also tiggers the expand function on click
 *
 * @param {Object} props Component props
 * @param {Array} props.tags Array of tags
 * @param {string} props.instanceId Id of the component
 * @param {string} props.placeholder Placeholder of the searcg input
 * @param props.isExpanded
 * @param props.disabled
 * @param props.setExpanded
 * @param props.onTagsChange
 * @return {JSX.Element}
 */
const Control = ( {
	tags = [],
	instanceId,
	placeholder,
	isExpanded,
	disabled,
	setExpanded = () => {},
	onTagsChange = () => {},
} ) => {
	const inputRef = useRef();
	const hasTags = tags.length > 0;
	const showPlaceholder = ! hasTags && ! isExpanded;

	return (
		// Disable reason: The div below visually simulates an input field. Its
		// child input is the actual input and responds accordingly to all keyboard
		// events, but click events need to be passed onto the child input. There
		// is no appropriate aria role for describing this situation, which is only
		// for the benefit of sighted users.
		/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
		<div
			className={ classnames(
				'components-base-control',
				'woocommerce-tree-select-control__control'
			) }
			onClick={ () => {
				inputRef.current.focus();
			} }
		>
			{ hasTags && (
				<Tags
					disabled={ disabled }
					tags={ tags }
					onChange={ onTagsChange }
				/>
			) }

			<div className="components-base-control__field">
				<input
					ref={ inputRef }
					id={ `woocommerce-select-control-${ instanceId }__control-input` }
					type="search"
					placeholder={ showPlaceholder ? placeholder : '' }
					autoComplete="off"
					className="woocommerce-tree-select-control__control-input"
					role="combobox"
					aria-autocomplete="list"
					aria-expanded={ isExpanded }
					aria-haspopup="true"
					disabled={ disabled }
					onFocus={ () => {
						setExpanded( true );
					} }
				/>
			</div>
		</div>
	);
};

export default Control;
