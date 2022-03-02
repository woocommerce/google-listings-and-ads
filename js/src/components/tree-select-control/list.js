/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';

/**
 * List component showing the option tree.
 *
 * @param {Object} props The component props
 * @param {Option[]} props.options The component options
 * @param {string[]} props.value Array of selected keys
 * @param {Function} props.onChange Callback when the options change
 * @return {JSX.Element} The component to be rendered
 */
const List = ( { options = [], value = [], onChange = () => {} } ) => {
	return (
		<div
			className="woocommerce-tree-select-control__listbox"
			role="listbox"
			tabIndex="-1"
		>
			<Options
				options={ options }
				value={ value }
				onChange={ onChange }
			/>
		</div>
	);
};

const Options = ( { options = [], value, parent = '', onChange } ) => {
	/**
	 * Returns true if all the children for the parent are selected
	 *
	 * @param {Option} option The parent option to check
	 */
	const isParentSelected = ( option ) => {
		if ( ! option.children ) {
			return false;
		}

		return option.children.every(
			( child ) => value.includes( child.id ) || isParentSelected( child )
		);
	};

	const hasChildren = ( option ) => !! option.children?.length;

	return options.map( ( option ) => {
		return (
			<div
				key={ `${ parent }-${ option.id }` }
				className="woocommerce-tree-select-control__group"
			>
				<CheckboxControl
					value={ option.id }
					className={ 'woocommerce-tree-select-control__option' }
					label={ option.name }
					checked={
						value.includes( option.id ) ||
						isParentSelected( option )
					}
					onChange={ ( checked ) => {
						onChange( checked, option );
					} }
				/>

				{ hasChildren( option ) && (
					<div className="woocommerce-tree-select-control__children">
						<Options
							parent={ option.id }
							options={ option.children }
							onChange={ onChange }
							value={ value }
						/>
					</div>
				) }
			</div>
		);
	} );
};

export default List;
