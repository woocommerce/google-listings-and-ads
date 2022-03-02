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
 * The Control Component renders a search input and also the Tags.
 * It also triggers the setExpand for expanding the options tree on click.
 *
 * @param {Object} props Component props
 * @param {Array} props.tags Array of tags
 * @param {string} props.instanceId Id of the component
 * @param {string} props.placeholder Placeholder of the search input
 * @param {boolean} props.isExpanded True if the tree is expanded
 * @param {boolean} props.disabled True if the component is disabled
 * @param {Function} props.onFocus On Focus Callback
 * @param {Function} props.onTagsChange Callback when the Tags change
 * @return {JSX.Element} The rendered component
 */
const Control = ( {
	tags = [],
	instanceId,
	placeholder,
	isExpanded,
	disabled,
	onFocus = () => {},
	onTagsChange = () => {},
} ) => {
	const inputRef = useRef();
	const hasTags = tags.length > 0;
	const showPlaceholder = ! hasTags && ! isExpanded;

	return (
		/*
		ESLint Disable reason
		https://github.com/woocommerce/woocommerce-admin/blob/main/packages/components/src/select-control/control.js#L200
		*/
		/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
		<div
			className={ classnames(
				'components-base-control',
				'woocommerce-tree-select-control__control',
				{
					'is-disabled': disabled,
					'has-tags': hasTags,
				}
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
					id={ `woocommerce-tree-select-control-${ instanceId }__control-input` }
					type="search"
					placeholder={ showPlaceholder ? placeholder : '' }
					autoComplete="off"
					className="woocommerce-tree-select-control__control-input"
					role="combobox"
					aria-autocomplete="list"
					aria-expanded={ isExpanded }
					aria-haspopup="true"
					disabled={ disabled }
					onFocus={ onFocus }
				/>
			</div>
		</div>
	);
};

export default Control;
