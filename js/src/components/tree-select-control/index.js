/**
 * External dependencies
 */
import { cloneDeep } from 'lodash';
import { __ } from '@wordpress/i18n';
import {
	useEffect,
	useMemo,
	useState,
	useRef,
	createRef,
	useCallback,
} from '@wordpress/element';
import classnames from 'classnames';
// eslint-disable-next-line import/no-extraneous-dependencies,@woocommerce/dependency-group,@wordpress/no-unsafe-wp-apis
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
import {
	ARROW_DOWN,
	ARROW_UP,
	BACKSPACE,
	ENTER,
	ESCAPE,
	ROOT_VALUE,
} from '.~/components/tree-select-control/constants';

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
 *
 * @typedef {Object} Option
 * @property {string} value The value for the option
 * @property {string} label The label for the option
 * @property {Option[]} [children] The children Option objects
 * @property {string} [key] Optional unique key for the Option. It will fallback to the value property if not defined
 *
 *
 *
 * @typedef {Object} RepositoryData
 * @property {Object} [ref] React Ref associated to the option. This is for system purposes only.
 *
 * @typedef {Option & RepositoryData} RepositoryOption
 **/

/**
 * Renders a component with a searchable control, tags and a tree selector.
 *
 * @param {Object} props Component props.
 * @param {string} props.id Component id
 * @param {string} props.label Label for the component
 * @param {string | false} props.selectAllLabel Label for the Select All root element. False for disable.
 * @param {string} props.placeholder Placeholder for the search control input
 * @param {string} props.className The class name for this component
 * @param {boolean} props.disabled Disables the component
 * @param {Option[]} props.options Options to show in the component
 * @param {string[]} props.value Selected values
 * @param {number} props.maxVisibleTags The maximum number of tags to show. Undefined, 0 or less than 0 evaluates to "Show All".
 * @param {Function} props.onChange Callback when the selector changes
 * @return {JSX.Element} The component
 */
const TreeSelectControl = ( {
	id,
	label,
	selectAllLabel = __( 'All', 'google-listings-and-ads' ),
	placeholder,
	className,
	disabled,
	options = [],
	value = [],
	maxVisibleTags,
	onChange = () => {},
} ) => {
	let instanceId = useInstanceId( TreeSelectControl );
	instanceId = id ?? instanceId;

	const [ treeVisible, setTreeVisible ] = useState( false );
	const [ nodesExpanded, setNodesExpanded ] = useState( [] );
	const [ inputControlValue, setInputControlValue ] = useState( '' );
	const [ filteredOptions, setFilteredOptions ] = useState( [] );
	const [ focused, setFocused ] = useState( null );

	// We will save in a REF previous search filter queries to avoid re-query the tree and save performance
	const filteredOptionsCache = useRef( {} );
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

	const hasChildren = ( option ) => option.children?.length;
	const filterQuery = inputControlValue.trim().toLowerCase();
	const filter = filterQuery.length >= 3 ? filterQuery : '';

	/**
	 * Optimizes the performance for getting the tags info
	 */
	const optionsRepository = useMemo( () => {
		const repository = {};

		filteredOptionsCache.current = []; // clear cache if options change

		function loadOption( option, parent ) {
			option.children?.forEach( ( child ) => {
				loadOption( child, option );
			} );

			repository[ option.key ?? option.value ] = {
				...option,
				parent,
				ref: createRef(),
			};
		}

		treeOptions.forEach( ( option ) => loadOption( option ) );

		return repository;
	}, [ treeOptions ] );

	/**
	 * Get the option parent
	 *
	 * @param {Option} option The option to get the parent
	 * @return {Option} The parent option
	 */
	const getOptionParent = ( option ) => {
		const parent = getOptionFromRepository( option )?.parent;
		return getOptionFromRepository( parent );
	};

	/**
	 * Get the option index
	 *
	 * @param {RepositoryOption} option The option to get the index
	 * @return {number} The index
	 */
	const getOptionIndex = ( option ) => {
		return Number( option.ref.current.dataset.index );
	};

	/**
	 * Get the Option last child recursively
	 *
	 * @param {Option} option The option to get the last child
	 * @return {Option} The last child in the option
	 */
	const getLastChild = ( option ) => {
		return nodesExpanded.includes( option.value ) ||
			option.value === ROOT_VALUE
			? getLastChild( option.children[ option.children.length - 1 ] )
			: getOptionFromRepository( option );
	};

	/**
	 * Get the previous available Option in the tree
	 *
	 * @param {Object} option The reference option to get the previous element
	 * @return {Object} The previous option
	 */
	const getPreviousOption = ( option ) => {
		if ( ! option ) {
			return getLastChild(
				filteredOptions[ filteredOptions.length - 1 ]
			);
		}

		const index = getOptionIndex( option );
		const parent = getOptionParent( option );

		// if the option has parent and is the first option in the group, then the previous option is the parent
		if ( parent && index === 0 ) {
			return getOptionFromRepository( parent );
		}

		return parent
			? getLastChild( parent.children[ index - 1 ] )
			: getLastChild(
					filteredOptions[
						index > 0 ? index - 1 : filteredOptions.length - 1
					]
			  );
	};

	/**
	 * Get the next option available in the tree
	 *
	 * The function attempts to get the next option in the current level
	 * if there is no more options, it tries to get the next option on the upper level.
	 *
	 * @param {Option} option TThe reference option to get the next element
	 * @return {Object} The next available option in the tree
	 */
	const getNextOption = ( option ) => {
		if ( ! option ) {
			return getOptionFromRepository( filteredOptions[ 0 ] );
		}

		if (
			nodesExpanded.includes( focused.value ) ||
			focused.value === ROOT_VALUE
		) {
			return getOptionFromRepository( focused.children[ 0 ] );
		}

		const index = getOptionIndex( option );
		const parent = getOptionParent( option );

		const nextSibling = parent
			? parent.children[ index + 1 ]
			: filteredOptions[ ( index + 1 ) % filteredOptions.length ];

		if ( nextSibling ) {
			return getOptionFromRepository( nextSibling );
		}

		return getNextOption( parent );
	};

	/**
	 * Gets an option from the repository using the option key or the option value
	 *
	 * @param {Option} option The option to get from the Repository
	 * @return {RepositoryOption|undefined} The Repository option or undefined if it's not found
	 */
	const getOptionFromRepository = useCallback(
		( option ) => {
			if ( ! option ) return;
			return optionsRepository[ option.key ?? option.value ];
		},
		[ optionsRepository ]
	);

	/**
	 * Perform the search query filter in the Tree options
	 *
	 * 1. Check if the search query is already cached and return it if so.
	 * 2. Deep Copy the tree. Since we are going to modify its children and labels recursively.
	 * 3. In case of filter, we apply the filter option function to the tree.
	 * 4. In the filter function we also highlight the label with the matching letters
	 * 5. Finally we set the cache with the obtained results and apply the filters
	 *
	 */
	useEffect( () => {
		const cachedFilteredOptions = filteredOptionsCache.current[ filter ];

		const highlightOptionLabel = ( optionLabel, matchPosition ) => {
			const matchLength = matchPosition + filter.length;

			if ( ! filter ) return optionLabel;

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

		const filterOption = ( option ) => {
			option.ref = getOptionFromRepository( option ).ref;

			if ( hasChildren( option ) ) {
				option.children = option.children.filter( filterOption );
				return !! option.children.length;
			}

			const match = option.label.toLowerCase().indexOf( filter );

			if ( match >= 0 ) {
				option.label = highlightOptionLabel( option.label, match );
				return true;
			}
		};

		if ( cachedFilteredOptions ) {
			setFilteredOptions( cachedFilteredOptions );
			return;
		}

		let filteredTreeOptions = cloneDeep( treeOptions );

		filteredTreeOptions = filteredTreeOptions.filter( filterOption );
		filteredOptionsCache.current[ filter ] = filteredTreeOptions;
		setFilteredOptions( filteredTreeOptions );
	}, [ treeOptions, filter, getOptionFromRepository ] );

	const onKeyDown = ( event ) => {
		if ( ESCAPE === event.key ) {
			setTreeVisible( false );
		}

		if ( ENTER === event.key ) {
			setTreeVisible( true );
			event.preventDefault();
		}

		if ( inputControlValue ) return;

		if ( ARROW_UP === event.key ) {
			setFocused( getPreviousOption( focused ) );
			event.preventDefault();
		}

		if ( ARROW_DOWN === event.key ) {
			setFocused( getNextOption( focused ) );
			event.preventDefault();
		}

		if ( BACKSPACE === event.key ) {
			onChange( value.slice( 0, -1 ) );
			event.preventDefault();
		}
	};

	useEffect( () => {
		if ( focused ) {
			focused.ref.current.focus();
		}
	}, [ focused ] );

	/**
	 * Get formatted Tags from the selected values.
	 *
	 * @return {{label: {string}, id: {string}}[]} An array of Tags
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

	/**
	 * Handles a change on the Tree options. Could be a click on a parent option
	 * or a child option
	 *
	 * @param {boolean} checked Indicates if the item should be checked
	 * @param {Option} option The option to change
	 */
	const handleOptionsChange = ( checked, option ) => {
		if ( hasChildren( option ) ) {
			handleParentChange( checked, option );
		} else {
			handleSingleChange( checked, option );
		}
	};

	/**
	 * Handles a change of a child element.
	 *
	 * @param {boolean} checked Indicates if the item should be checked
	 * @param {Option} option The option to change
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
	 * @param {Option} option The option to change
	 */
	const handleParentChange = ( checked, option ) => {
		const newValue = [ ...value ];

		function loadChildren( parent ) {
			if ( ! parent.children ) {
				return;
			}

			parent.children.forEach( ( child ) => {
				if ( hasChildren( child ) ) {
					loadChildren( child );
					return;
				}

				const childPosition = newValue.indexOf( child.value );

				if ( ! checked && childPosition >= 0 ) {
					newValue.splice( childPosition, 1 );
				}

				if ( checked && childPosition < 0 ) {
					newValue.push( child.value );
				}
			} );
		}

		loadChildren( option );
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

	const handleOptionFocused = ( option ) => {
		setFocused( option );
	};

	return (
		// eslint-disable-next-line jsx-a11y/no-static-element-interactions
		<div
			{ ...focusOutside }
			onKeyDown={ onKeyDown }
			onClick={ () => {
				setTreeVisible( true );
			} }
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
				disabled={ disabled }
				tags={ getTags() }
				isExpanded={ treeVisible }
				onFocus={ () => {
					setFocused( null );
					setTreeVisible( true );
				} }
				instanceId={ instanceId }
				placeholder={ placeholder }
				label={ label }
				maxVisibleTags={ maxVisibleTags }
				onTagsChange={ handleTagsChange }
				onInputChange={ handleOnInputChange }
			/>
			{ treeVisible && (
				<div
					className="woocommerce-tree-select-control__tree"
					role="tree"
					tabIndex="-1"
				>
					<Options
						options={ filteredOptions }
						value={ value }
						isFiltered={ !! filter }
						onChange={ handleOptionsChange }
						nodesExpanded={ nodesExpanded }
						onNodesExpandedChange={ setNodesExpanded }
						onOptionFocused={ handleOptionFocused }
					/>
				</div>
			) }
		</div>
	);
};

export default TreeSelectControl;
