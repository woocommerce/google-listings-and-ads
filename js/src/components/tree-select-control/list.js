/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import classnames from 'classnames';

/**
 * List component showing the option tree.
 *
 * @param {Object} props The component props
 * @param {Option[]} props.options The component options
 * @param {string[]} props.selected Array of selected keys
 * @param {Function} props.onChange Callback when the options change
 * @return {JSX.Element} The component to be rendered
 */
const List = ( { options = [], selected = [], onChange = () => {} } ) => {
	const hasChildren = ( parent ) => !! parent.children?.length;

	/**
	 * Returns true if all the children for the parent are selected
	 *
	 * @param {Option} parent The parent option to check
	 */
	const isParentSelected = ( parent ) =>
		parent.children.every( ( { id } ) => selected.includes( id ) );

	return (
		<div
			className="woocommerce-tree-select-control__listbox"
			role="listbox"
			tabIndex="-1"
		>
			{ options.map( ( parent ) => {
				return (
					<div
						key={ parent.id }
						className="woocommerce-tree-select-control__group"
					>
						<CheckboxControl
							value={ parent.id }
							className={ classnames(
								'woocommerce-tree-select-control__option',
								'woocommerce-tree-select-control__parent'
							) }
							label={ parent.name }
							checked={ isParentSelected( parent ) }
							onChange={ ( checked ) => {
								onChange( checked, parent );
							} }
						/>
						{ hasChildren( parent ) && (
							<div className="woocommerce-tree-select-control__children">
								{ parent.children.map( ( children ) => {
									return (
										<CheckboxControl
											key={ children.id }
											className={ classnames(
												'woocommerce-tree-select-control__option',
												'woocommerce-tree-select-control__child'
											) }
											value={ children.id }
											label={ children.name }
											checked={ selected.includes(
												children.id
											) }
											onChange={ ( checked ) => {
												onChange( checked, children );
											} }
										/>
									);
								} ) }
							</div>
						) }
					</div>
				);
			} ) }
		</div>
	);
};

export default List;
