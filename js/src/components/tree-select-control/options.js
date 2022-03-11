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
 * @param {Object} props Component parameters
 * @param {Option[]} props.options List of options to be rendered
 * @param {string[]} props.value List of selected values
 * @param {string[]} props.nodesExpanded List of expanded nodes.
 * @param {string} props.filter The filter applied to the Tree Options
 * @param {Function} props.onChange Callback when an option changes
 * @param {Function} props.onNodesExpandedChange Callback when a node is expanded/collapsed
 */
const Options = ( props ) => {
	const {
		options = [],
		value = [],
		filter,
		nodesExpanded = [],
		onChange = () => {},
		onNodesExpandedChange = () => {},
	} = props;
	/**
	 * Returns true if all the children for the parent are selected
	 *
	 * @param {Option} parent The parent option to check
	 */
	const isEveryChildrenSelected = ( parent ) => {
		if ( ! parent.children?.length ) {
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

	const highlightedLabel = ( option ) => {
		if ( ! filter.length || option.children?.length ) return option.label;

		const highlightPosition = option.label.indexOf( filter );

		return (
			<span>
				<span>{ option.label.substring( 0, highlightPosition ) }</span>
				<strong>
					{ option.label.substring(
						highlightPosition,
						highlightPosition + filter.length
					) }
				</strong>
				<span>
					{ option.label.substring(
						highlightPosition + filter.length
					) }
				</span>
			</span>
		);
	};

	return options.map( ( option ) => {
		const isRoot = option.value === '';
		const hasChildren = !! option.children?.length;
		const isExpanded =
			filter.length || isRoot || nodesExpanded.includes( option.value );

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
						className={ 'woocommerce-tree-select-control__option' }
						value={ option.value }
						label={ highlightedLabel( option ) }
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
							isRoot && 'woocommerce-tree-select-control__main'
						) }
					>
						<Options
							options={ option.children }
							filter={ filter }
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
