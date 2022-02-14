/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import classnames from 'classnames';

const List = ( { options = [], selected = [], onChange = () => {} } ) => {
	const hasChildren = ( parent ) => parent.children?.length;

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
							className={ classnames(
								'woocommerce-tree-select-control__parent',
								'woocommerce-tree-select-control__option'
							) }
							value={ parent.id }
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
												'woocommerce-tree-select-control__child',
												'woocommerce-tree-select-control__option'
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
