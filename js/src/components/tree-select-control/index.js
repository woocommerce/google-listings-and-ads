/**
 * External dependencies
 */
import { noop } from 'lodash';
import { __ } from '@wordpress/i18n';
import { focus } from '@wordpress/dom';
import { useEffect, useMemo, useState, useRef } from '@wordpress/element';
import classnames from 'classnames';
import {
	__experimentalUseFocusOutside as useFocusOutside,
	useInstanceId,
} from '@wordpress/compose';

/**
 * Internal dependencies
 */
import useIsEqualRefValue from '.~/hooks/useIsEqualRefValue';
import Control from './control';
import Options from './options';
import './index.scss';
import { ARROW_DOWN, ARROW_UP, ENTER, ESCAPE, ROOT_VALUE } from './constants';

/**
 * Example of Options data structure:
 *   [
       {
			value: 'EU',
			label: 'Europe',
			children: [
				{ value: 'ES', label: 'Spain' },
				{ value: 'FR', label: 'France', children: [] }, // defining children as [] is equivalent to don't have children
			],
		},
		{
			value: 'NA',
			label: 'North America',
			children: [
				{ value: 'US', label: 'United States', [
					{ value: 'TX', label: 'Texas' },
					{ value: 'NY', label: 'New York' },
				] },
				{ value: 'CA', label: 'Canada' },
			],
		}
     ],
 **/

/**
 * @typedef {Object} CommonOption
 * @property {string} value The value for the option
 * @property {string} [key] Optional unique key for the Option. It will fallback to the value property if not defined
 */

/**
 * @typedef {Object} BaseOption
 * @property {string} label The label for the option
 * @property {Option[]} [children] The children Option objects
 *
 * @typedef {CommonOption & BaseOption} Option
 */

/**
 * @typedef {Object} BaseInnerOption
 * @property {string|JSX.Element} label The label string or label with highlighted react element for the option.
 * @property {InnerOption[]|undefined} children The children options. The options are filtered if in searching.
 * @property {boolean} isRoot Whether this option is the root.
 * @property {boolean} hasChildren Whether this option has children.
 * @property {InnerOption[]} leaves All leaf options that are flattened under this option. The options are filtered if in searching.
 * @property {boolean} checked Whether this option is checked.
 * @property {boolean} partialChecked Whether this option is partially checked.
 * @property {boolean} expanded Whether this option is expanded.
 * @property {boolean} parent The parent of the current option
 *
 * @typedef {CommonOption & BaseInnerOption} InnerOption
 */

/**
 * Renders a component with a searchable control, tags and a tree selector.
 *
 * @param {Object} props Component props.
 * @param {string} [props.id] Component id
 * @param {string} [props.label] Label for the component
 * @param {string | false} [props.selectAllLabel] Label for the Select All root element. False for disable.
 * @param {string} [props.help] Help text under the select input.
 * @param {string} [props.placeholder] Placeholder for the search control input
 * @param {string} [props.className] The class name for this component
 * @param {boolean} [props.disabled] Disables the component
 * @param {Option[]} [props.options] Options to show in the component
 * @param {string[]} [props.value] Selected values
 * @param {number} [props.maxVisibleTags] The maximum number of tags to show. Undefined, 0 or less than 0 evaluates to "Show All".
 * @param {boolean} [props.allowsSelectParents=false] If true, when selecting a parent it also adds its key in the onChange callback.
 * @param {Function} [props.onChange] Callback when the selector changes
 * @param {(visible: boolean) => void} [props.onDropdownVisibilityChange] Callback when the visibility of the dropdown options is changed.
 * @return {JSX.Element} The component
 */
const TreeSelectControl = ( {
	id,
	label,
	selectAllLabel = __( 'All', 'google-listings-and-ads' ),
	help,
	placeholder,
	className,
	disabled,
	options = [],
	value = [],
	maxVisibleTags,
	allowsSelectParents = false,
	onChange = () => {},
	onDropdownVisibilityChange = noop,
} ) => {
	let instanceId = useInstanceId( TreeSelectControl );
	instanceId = id ?? instanceId;

	const [ treeVisible, setTreeVisible ] = useState( false );
	const [ nodesExpanded, setNodesExpanded ] = useState( [] );
	const [ inputControlValue, setInputControlValue ] = useState( '' );

	const controlRef = useRef();
	const dropdownRef = useRef();
	const onDropdownVisibilityChangeRef = useRef();
	onDropdownVisibilityChangeRef.current = onDropdownVisibilityChange;

	// We will save in a REF previous search filter queries to avoid re-query the tree and save performance
	const cacheRef = useRef( { filteredOptionsMap: new Map() } );
	cacheRef.current.expandedValues = nodesExpanded;
	cacheRef.current.selectedValues = value;

	const showTree = ! disabled && treeVisible;

	const root =
		selectAllLabel !== false
			? {
					label: selectAllLabel,
					value: ROOT_VALUE,
					children: options,
			  }
			: null;

	const treeOptions = useIsEqualRefValue( root ? [ root ] : options );

	const focusOutside = useFocusOutside( () => {
		setTreeVisible( false );
	} );

	const filterQuery = inputControlValue.trim().toLowerCase();
	const filter = filterQuery.length >= 3 ? filterQuery : '';

	/**
	 * Optimizes the performance for getting the tags info
	 */
	const optionsRepository = useMemo( () => {
		const repository = {};

		// Clear cache if options change
		cacheRef.current.filteredOptionsMap.clear();

		function loadOption( option, parentId ) {
			option.parent = parentId;

			option.children?.forEach( ( el ) =>
				loadOption( el, option.value )
			);

			repository[ option.key ?? option.value ] = option;
		}

		treeOptions.forEach( loadOption );

		return repository;
	}, [ treeOptions ] );

	/*
	 * Perform the search query filter in the Tree options
	 *
	 * 1. Check if the search query is already cached and return it if so.
	 * 2. Deep copy the tree with adding properties for rendering.
	 * 3. In case of filter, we apply the filter option function to the tree.
	 * 4. In the filter function we also highlight the label with the matching letters
	 * 5. Finally we set the cache with the obtained results and apply the filters
	 */
	const filteredOptions = useMemo( () => {
		const { current: cache } = cacheRef;
		const cachedFilteredOptions = cache.filteredOptionsMap.get( filter );

		if ( cachedFilteredOptions ) {
			return cachedFilteredOptions;
		}

		const isSearching = Boolean( filter );

		const highlightOptionLabel = ( optionLabel, matchPosition ) => {
			const matchLength = matchPosition + filter.length;

			if ( ! isSearching ) return optionLabel;

			return (
				<span>
					<span>{ optionLabel.substring( 0, matchPosition ) }</span>
					<strong>
						{ optionLabel.substring( matchPosition, matchLength ) }
					</strong>
					<span>{ optionLabel.substring( matchLength ) }</span>
				</span>
			);
		};

		const descriptors = {
			isRoot: {
				/**
				 * Returns whether this option is the root.
				 *
				 * @return {boolean} True if the option is the root.
				 */
				get() {
					return this.value === ROOT_VALUE;
				},
			},
			hasChildren: {
				/**
				 * Returns whether this option has children.
				 *
				 * @return {boolean} True if has children, false otherwise.
				 */
				get() {
					return this.children?.length > 0;
				},
			},
			leaves: {
				/**
				 * Return all leaf options flattened under this option. The options are filtered if in searching.
				 *
				 * @return {InnerOption[]} All leaf options that are flattened under this option. The options are filtered if in searching.
				 */
				get() {
					if ( ! this.hasChildren ) {
						return [];
					}
					return this.children.flatMap( ( option ) => {
						return option.hasChildren ? option.leaves : option;
					} );
				},
			},
			checked: {
				/**
				 * Returns whether this option is checked.
				 * A leaf option is checked if its value is selected.
				 * When allowsSelectParents is false. Then a parent option is checked if all leaves are checked.
				 * When allowsSelectParents is true. Then a parent option is checked if its value is selected.
				 *
				 * @return {boolean} True if checked, false otherwise.
				 */
				get() {
					if (
						this.hasChildren &&
						( ! allowsSelectParents || this.isRoot )
					) {
						return this.leaves.every( ( opt ) => opt.checked );
					}
					return cache.selectedValues.includes( this.value );
				},
			},
			partialChecked: {
				/**
				 * Returns whether this option is partially checked.
				 * A leaf option always returns false.
				 * A parent option is partially checked if at least one but not all leaves are checked.
				 *
				 * @return {boolean} True if partially checked, false otherwise.
				 */
				get() {
					if ( ! this.hasChildren ) {
						return false;
					}
					return (
						! this.checked &&
						this.leaves.some(
							( opt ) => opt.checked || opt.partialChecked
						)
					);
				},
			},
			expanded: {
				/**
				 * Returns whether this option is expanded.
				 * A leaf option always returns false.
				 *
				 * @return {boolean} True if expanded, false otherwise.
				 */
				get() {
					return (
						isSearching ||
						this.isRoot ||
						cache.expandedValues.includes( this.value )
					);
				},
			},
		};

		const reduceOptions = ( acc, { children = [], ...option } ) => {
			let match = 0;

			// its a searchable option if this two conditions apply:
			// 1. its not Root and
			// 2. is a leaf or selecting parents is allowed.
			const isSearchable =
				( ! option.children || allowsSelectParents ) &&
				option.value !== ROOT_VALUE;

			if ( children.length ) {
				// Iterate over the children to find a match on each child recursively
				option.children = children.reduce( reduceOptions, [] );

				// If the option.children property is empty here, it means there is no matches in the children neither.
				// So in case we don't allow parent selection, we can return the empty acc for this option as it shouldn't show in the tree.
				if ( ! option.children.length && ! allowsSelectParents ) {
					return acc;
				}
			}

			if ( isSearching ) {
				match = option.label.toLowerCase().indexOf( filter );

				if ( match >= 0 && isSearchable ) {
					// only highlight the label if there is a match or its a searchable option
					option.label = highlightOptionLabel( option.label, match );
				}
			}

			if ( ( match >= 0 && isSearchable ) || option.children?.length ) {
				// only add the option to the tree if:
				// a. there are matching children for this option.
				// b. there is a match for the option itself, and its a searchable option.
				Object.defineProperties( option, descriptors );
				acc.push( option );
			}

			return acc;
		};

		const filteredTreeOptions = treeOptions.reduce( reduceOptions, [] );
		cache.filteredOptionsMap.set( filter, filteredTreeOptions );

		return filteredTreeOptions;
	}, [ treeOptions, filter, allowsSelectParents ] );

	const onKeyDown = ( event ) => {
		if ( disabled ) return;

		if ( ESCAPE === event.key ) {
			setTreeVisible( false );
		}

		if ( ENTER === event.key ) {
			setTreeVisible( true );
			event.preventDefault();
		}

		const stepDict = {
			[ ARROW_UP ]: -1,
			[ ARROW_DOWN ]: 1,
		};
		const step = stepDict[ event.key ];

		if ( step && dropdownRef.current && filteredOptions.length ) {
			const elements = focus.focusable
				.find( dropdownRef.current )
				.filter( ( el ) => el.type === 'checkbox' );
			const currentIndex = elements.indexOf( event.target );
			const index = Math.max( currentIndex + step, -1 ) % elements.length;
			elements.at( index ).focus();
			event.preventDefault();
		}
	};

	useEffect( () => {
		onDropdownVisibilityChangeRef.current( showTree );
	}, [ showTree ] );

	/**
	 * Get formatted Tags from the selected values.
	 *
	 * @return {Array<{id: string, label: string|undefined}>} An array of Tags
	 */
	const getTags = () => {
		if ( ! options.length ) {
			return [];
		}

		return value.map( ( key ) => {
			const option = optionsRepository[ key ];
			return { id: key, label: option?.label };
		} );
	};

	const handleExpanderClick = ( e ) => {
		const elements = focus.focusable.find( dropdownRef.current );
		const index = elements.indexOf( e.currentTarget ) + 1;
		elements.at( index ).focus();
	};

	/**
	 * Expands/Collapses the Option
	 *
	 * @param {InnerOption} option The option to be expanded or collapsed.
	 */
	const handleToggleExpanded = ( option ) => {
		setNodesExpanded(
			option.expanded
				? nodesExpanded.filter( ( el ) => option.value !== el )
				: [ ...nodesExpanded, option.value ]
		);
	};

	/**
	 * Handles a change on the Tree options. Could be a click on a parent option
	 * or a child option
	 *
	 * @param {boolean} checked Indicates if the item should be checked
	 * @param {InnerOption} option The option to change
	 */
	const handleOptionsChange = ( checked, option ) => {
		if ( option.hasChildren ) {
			handleParentChange( checked, option );
		} else {
			handleSingleChange( checked, option );
		}

		setInputControlValue( '' );
		if ( ! nodesExpanded.includes( option.parent ) ) {
			controlRef.current.focus();
		}
	};

	/**
	 * Handles a change of a child element.
	 *
	 * @param {boolean} checked Indicates if the item should be checked
	 * @param {InnerOption} option The option to change
	 */
	const handleSingleChange = ( checked, option ) => {
		const newValue = checked
			? [ ...value, option.value ]
			: value.filter( ( el ) => el !== option.value );

		onChange( newValue );
	};

	/**
	 * Handles a change of a Parent element.
	 *
	 * @param {boolean} checked Indicates if the item should be checked
	 * @param {InnerOption} option The option to change
	 */
	const handleParentChange = ( checked, option ) => {
		// If allowsSelectParents is true we allow the parent key to be selected and we don't select the children, we just expand the node.
		if ( allowsSelectParents && ! option.isRoot ) {
			handleSingleChange( checked, option );
			if ( ! option.expanded ) {
				handleToggleExpanded( option );
			}
			return;
		}

		const flattenChildren = ( children ) =>
			Array.prototype.concat.apply(
				children,
				children.map( ( child ) =>
					flattenChildren( child.children || [] )
				)
			);

		let newValue;
		const selectableValues = allowsSelectParents
			? flattenChildren( option.children ).map(
					( child ) => delete child.children && child
			  )
			: option.leaves;

		const changedValues = selectableValues
			.filter( ( opt ) => opt.checked !== checked )
			.map( ( opt ) => opt.value );

		if ( checked ) {
			if ( ! option.expanded ) {
				handleToggleExpanded( option );
			}
			newValue = value.concat( changedValues );
		} else {
			newValue = value.filter( ( el ) => ! changedValues.includes( el ) );
		}

		onChange( newValue );
	};

	/**
	 * Handles a change of a Tag element. We map them to Value format.
	 *
	 * @param {Array} tags List of current tags
	 */
	const handleTagsChange = ( tags ) => {
		onChange( [ ...tags.map( ( el ) => el.id ) ] );
	};

	/**
	 * Prepares and sets the search filter.
	 * Filters of less than 3 characters are not considered, so we convert them to ''
	 *
	 * @param {Event} e Event returned by the On Change function in the Input control
	 */
	const handleOnInputChange = ( e ) => {
		setInputControlValue( e.target.value );
	};

	return (
		// eslint-disable-next-line jsx-a11y/no-static-element-interactions
		<div
			{ ...focusOutside }
			onKeyDown={ onKeyDown }
			className={ classnames(
				'woocommerce-tree-select-control',
				className
			) }
		>
			{ !! label && (
				<label
					htmlFor={ `woocommerce-tree-select-control-${ instanceId }__control-input` }
					className="woocommerce-tree-select-control__label"
				>
					{ label }
				</label>
			) }

			<Control
				ref={ controlRef }
				disabled={ disabled }
				tags={ getTags() }
				isExpanded={ showTree }
				onFocus={ () => {
					setTreeVisible( true );
				} }
				onControlClick={ () => {
					if ( disabled ) return;
					setTreeVisible( true );
				} }
				instanceId={ instanceId }
				placeholder={ placeholder }
				label={ label }
				maxVisibleTags={ maxVisibleTags }
				value={ inputControlValue }
				onTagsChange={ handleTagsChange }
				onInputChange={ handleOnInputChange }
			/>
			{ showTree && (
				<div
					ref={ dropdownRef }
					className="woocommerce-tree-select-control__tree"
					role="tree"
					tabIndex="-1"
				>
					<Options
						options={ filteredOptions }
						onChange={ handleOptionsChange }
						onExpanderClick={ handleExpanderClick }
						onToggleExpanded={ handleToggleExpanded }
					/>
				</div>
			) }
			{ help && (
				<div className="woocommerce-tree-select-control__help">
					{ help }
				</div>
			) }
		</div>
	);
};

export default TreeSelectControl;
