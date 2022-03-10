/**
 * External dependencies
 */
import { CheckboxControl, Flex } from '@wordpress/components';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import classnames from 'classnames';

/**
 * @typedef {import('./').Option} Option
 */

/**
 * This component renders a list of options and its children recursively
 *
 * @param {Object} params Component parameters
 * @param {Option[]} params.options List of options to be rendered
 * @param {string[]} params.value List of selected values
 * @param {string[]} params.nodesExpanded List of expanded nodes.
 * @param {Function} params.onChange Callback when an option changes
 * @param {Function} params.onNodesExpandedChange Callback when a node is expanded/collapsed
 */
const Options = ( {
	options = [],
	value = [],
	onChange = () => {},
	nodesExpanded = [],
	onNodesExpandedChange = () => {},
} ) => {
	/**
	 * Returns true if all the children for the parent are selected
	 *
	 * @param {Option} parent The parent option to check
	 */
	const isEveryChildrenSelected = ( parent ) => {
		if ( ! parent.children ) {
			return false;
		}

		return parent.children.every(
			( child ) =>
				value.includes( child.value ) ||
				isEveryChildrenSelected( child )
		);
	};

	const toggleExpanded = ( option ) => {
		onNodesExpandedChange(
			nodesExpanded.includes( option.value )
				? nodesExpanded.filter( ( el ) => option.value !== el )
				: [ ...nodesExpanded, option.value ]
		);
	};

	return options.map( ( option ) => {
		const isRoot = option.value === '';

		const isExpanded = isRoot || nodesExpanded.includes( option.value );

		const hasChildren = !! option.children?.length;

		return (
			<div
				key={ `${ option.value }` }
				role={ hasChildren ? 'treegroup' : 'treeitem' }
				aria-expanded={ hasChildren ? isExpanded : undefined }
			>
				<Flex
					className="woocommerce-tree-select-control__node"
					justify="flex-start"
				>
					{ ! isRoot && (
						<Icon
							className={ classnames(
								'woocommerce-tree-select-control__expander-icon',
								! hasChildren ? 'is-hidden' : false
							) }
							icon={ isExpanded ? chevronUp : chevronDown }
							onClick={ () => {
								toggleExpanded( option );
							} }
						/>
					) }

					<CheckboxControl
						className={ 'woocommerce-tree-select-control__option' }
						value={ option.value }
						label={ option.label }
						checked={
							value.includes( option.value ) ||
							isEveryChildrenSelected( option )
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
							isRoot
								? 'woocommerce-tree-select-control__main'
								: false
						) }
					>
						<Options
							options={ option.children }
							value={ value }
							onChange={ onChange }
							nodesExpanded={ nodesExpanded }
							onNodesExpandedChange={ onNodesExpandedChange }
						/>
					</div>
				) }
			</div>
		);
	} );
};

export default Options;
