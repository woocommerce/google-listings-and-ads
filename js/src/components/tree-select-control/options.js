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
	 * Verifies if an option is checked.
	 * An option is checked if their value is selected or all of their children are selected
	 *
	 * @param {Option} option The option to verify if is checked
	 * @return {boolean} True if checked, false otherwise
	 */
	const isChecked = ( option ) => {
		return (
			value.includes( option.value ) || isEveryChildrenSelected( option )
		);
	};

	/**
	 * Verifies if an option has some children checked.
	 *
	 * @param {Option} parent the Option to verify
	 * @return {boolean} True if any at least one of the children is checked, false otherwsie
	 */
	const hasSomeChildrenChecked = ( parent ) => {
		if ( ! parent.children?.length ) {
			return false;
		}

		return parent.children.some(
			( child ) => isChecked( child ) || hasSomeChildrenChecked( child )
		);
	};

	/**
	 * Returns true if all the children for the parent are selected
	 *
	 * @param {Option} parent The parent option to check
	 */
	const isEveryChildrenSelected = ( parent ) => {
		if ( ! parent.children?.length ) {
			return false;
		}

		return parent.children.every( ( child ) => isChecked( child ) );
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
		const optionIsChecked = isChecked( option );

		return (
			<div
				key={ `${ option.value }` }
				role={ hasChildren ? 'treegroup' : 'treeitem' }
				aria-expanded={ hasChildren ? isExpanded : undefined }
				className={ classnames(
					'woocommerce-tree-select-control__node',
					hasChildren && 'has-children'
				) }
			>
				<Flex justify="flex-start">
					{ ! isRoot && (
						<button
							onClick={ () => {
								toggleExpanded( option );
							} }
							className={ classnames(
								'woocommerce-tree-select-control__expander',
								! hasChildren && 'is-hidden'
							) }
						>
							<Icon
								icon={ isExpanded ? chevronUp : chevronDown }
							/>
						</button>
					) }

					<CheckboxControl
						className={ classnames(
							'woocommerce-tree-select-control__option',
							! optionIsChecked &&
								hasSomeChildrenChecked( option ) &&
								'is-partially-checked'
						) }
						value={ option.value }
						label={ option.label }
						checked={ optionIsChecked }
						onChange={ ( checked ) => {
							onChange( checked, option );
						} }
					/>
				</Flex>

				{ hasChildren && isExpanded && (
					<div
						className={ classnames(
							'woocommerce-tree-select-control__children',
							isRoot && 'woocommerce-tree-select-control__main'
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
