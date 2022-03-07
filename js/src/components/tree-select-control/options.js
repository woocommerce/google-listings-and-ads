/**
 * External dependencies
 */
import { CheckboxControl, Flex } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import classnames from 'classnames';

/**
 * This component renders a list of options and its children recursively
 *
 * @param {Object} params Component parameters
 * @param {Option[]} params.options List of options to be rendered
 * @param {string[]} params.value List of selected values
 * @param {string} params.parent The parent of these set of options
 * @param {Function} params.onChange Callback when an option changes
 */
const Options = ( {
	options = [],
	value = [],
	parent = '',
	onChange = () => {},
} ) => {
	const [ expanded, setExpanded ] = useState( [] );
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
			( child ) =>
				value.includes( child.value ) || isParentSelected( child )
		);
	};

	const toggleExpanded = ( option ) => {
		setExpanded(
			expanded.includes( option )
				? expanded.filter( ( el ) => option !== el )
				: [ ...expanded, option ]
		);
	};

	const isRoot = ( option ) => option.value === 'all';

	return options.map( ( option ) => {
		const isExpanded =
			isRoot( option ) || expanded.includes( option.value );
		const hasChildren = !! option.children?.length;

		return (
			<div
				key={ `${ parent }-${ option.value }` }
				role={ hasChildren ? 'treegroup' : 'treeitem' }
				aria-expanded={ hasChildren ? isExpanded : undefined }
			>
				<Flex
					className="woocommerce-tree-select-control__node"
					justify="flex-start"
				>
					{ ! isRoot( option ) && (
						<Icon
							className={ classnames(
								'woocommerce-tree-select-control__expander-icon',
								! hasChildren ? 'is-hidden' : false
							) }
							icon={ isExpanded ? chevronUp : chevronDown }
							onClick={ () => {
								toggleExpanded( option.value );
							} }
						/>
					) }

					<CheckboxControl
						className={ 'woocommerce-tree-select-control__option' }
						value={ option.value }
						label={ option.label }
						checked={
							value.includes( option.value ) ||
							isParentSelected( option )
						}
						onChange={ ( checked ) => {
							onChange( checked, option );
						} }
					/>
				</Flex>

				{ hasChildren && isExpanded && (
					<div
						className={ classnames(
							'woocommerce-tree-select-control__children',
							isRoot( option )
								? 'woocommerce-tree-select-control__main'
								: false
						) }
					>
						<Options
							parent={ option.value }
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

export default Options;
