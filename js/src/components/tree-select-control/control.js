/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Tags from './tags';

const Control = ( {
	instanceId,
	placeholder,
	isExpanded,
	setExpanded = () => {},
} ) => {
	return (
		<div
			className={ classnames(
				'components-base-control',
				'woocommerce-tree-select-control__control'
			) }
		>
			<div className="components-base-control__field">
				<input
					id={ `woocommerce-select-control-${ instanceId }__control-input` }
					type="search"
					placeholder={ isExpanded ? '' : placeholder }
					autoComplete="off"
					className="woocommerce-tree-select-control__control-input"
					role="combobox"
					aria-autocomplete="list"
					aria-expanded={ isExpanded }
					aria-haspopup="true"
					onClick={ () => {
						setExpanded( true );
					} }
					// aria-owns={ listboxId }
					// aria-controls={ listboxId }
					// aria-activedescendant={ activeId }
					// aria-describedby={
					// 	hasTags && inlineTags
					// 		? `search-inline-input-${ instanceId }`
					// 		: null
					// }
				/>
			</div>
		</div>
	);
};

export default Control;
