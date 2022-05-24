/**
 * External dependencies
 */
import { noop } from 'lodash';
import { Flex } from '@wordpress/components';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { ARROW_LEFT, ARROW_RIGHT, ROOT_VALUE } from './constants';
import Checkbox from '.~/components/tree-select-control/checkbox';

/**
 * @typedef {import('./index').InnerOption} InnerOption
 */

/**
 * This component renders a list of options and its children recursively
 *
 * @param {Object} props Component parameters
 * @param {InnerOption[]} props.options List of options to be rendered
 * @param {string[]} props.value List of selected values
 * @param {string[]} props.nodesExpanded List of expanded nodes.
 * @param {Function} props.onChange Callback when an option changes
 * @param {Function} props.onNodesExpandedChange Callback when a node is expanded/collapsed
 * @param {Function} [props.onExpanderClick] Callback when an expander is clicked.
 */
const Options = ( {
	options = [],
	value = [],
	onChange = () => {},
	nodesExpanded = [],
	onNodesExpandedChange = () => {},
	onExpanderClick = noop,
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

	/**
	 * Expands/Collapses the Option
	 *
	 * @param {Option} option The option to be expanded or collapsed
	 */
	const toggleExpanded = ( option ) => {
		if ( ! option.children?.length ) return;

		onNodesExpandedChange(
			nodesExpanded.includes( option.value )
				? nodesExpanded.filter( ( el ) => option.value !== el )
				: [ ...nodesExpanded, option.value ]
		);
	};

	/**
	 * Alters the node with some keys for accessibility
	 * ArrowRight - Expands the node
	 * ArrowLeft - Collapses the node
	 *
	 * @param {Event} event The KeyDown event
	 * @param {InnerOption} option The option where the event happened
	 */
	const handleKeyDown = ( event, option ) => {
		if ( ! option.hasChildren ) {
			return;
		}
		if ( event.key === ARROW_RIGHT && ! option.expanded ) {
			toggleExpanded( option );
		} else if ( event.key === ARROW_LEFT && option.expanded ) {
			toggleExpanded( option );
		}
	};

	return options.map( ( option ) => {
		const isRoot = option.value === ROOT_VALUE;
		const { hasChildren, expanded } = option;
		const checked = isChecked( option );

		return (
			<div
				key={ `${ option.key ?? option.value }` }
				role={ hasChildren ? 'treegroup' : 'treeitem' }
				aria-expanded={ hasChildren ? expanded : undefined }
				className={ classnames(
					'woocommerce-tree-select-control__node',
					hasChildren && 'has-children'
				) }
			>
				<Flex justify="flex-start">
					{ ! isRoot && (
						<button
							className={ classnames(
								'woocommerce-tree-select-control__expander',
								! hasChildren && 'is-hidden'
							) }
							tabIndex="-1"
							onClick={ ( e ) => {
								onExpanderClick( e );
								toggleExpanded( option );
							} }
						>
							<Icon icon={ expanded ? chevronUp : chevronDown } />
						</button>
					) }

					<Checkbox
						className={ classnames(
							'components-base-control',
							'woocommerce-tree-select-control__option',
							! checked &&
								hasSomeChildrenChecked( option ) &&
								'is-partially-checked'
						) }
						option={ option }
						checked={ checked }
						onChange={ ( e ) => {
							onChange( e.target.checked, option );
						} }
						onKeyDown={ ( e ) => {
							handleKeyDown( e, option );
						} }
					/>
				</Flex>

				{ hasChildren && expanded && (
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
							onExpanderClick={ onExpanderClick }
						/>
					</div>
				) }
			</div>
		);
	} );
};

export default Options;
