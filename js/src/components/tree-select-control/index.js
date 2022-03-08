/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo, useState } from '@wordpress/element';
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

/**
 * The Option type Object. This is how we send the options to the selector.
 *
 * @typedef { Object } Option
 * @property {string} value The unique value for the option
 * @property {string} label The label for the option
 * @property {Option[]} [children] The children Option objects
 */

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
 * @param {{string}[]} props.value Selected values
 * @param {Function} props.onChange Callback when the selector changes
 * @return {JSX.Element|null} The component
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
	onChange = () => {},
} ) => {
	let instanceId = useInstanceId( TreeSelectControl );
	instanceId = id ?? instanceId;
	const [ isExpanded, setIsExpanded ] = useState( false );
	const optionsRef = useIsEqualRefValue(
		selectAllLabel
			? [ { label: selectAllLabel, value: '', children: options } ]
			: options
	);

	const focusOutside = useFocusOutside( () => {
		setIsExpanded( false );
	} );

	/**
	 * Optimizes the performance for getting the tags info
	 *
	 * @see getTags
	 */
	const optionsRepository = useMemo( () => {
		const repository = {};

		function loadOption( option ) {
			if ( ! option.children ) {
				repository[ option.value ] = { ...option };
			} else {
				option.children.forEach( ( child ) => {
					loadOption( child );
				} );
			}
		}

		optionsRef.forEach( ( option ) => loadOption( option ) );

		return repository;
	}, [ optionsRef ] );

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
			return { id: key, label: option.label };
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
		if ( option.children?.length ) {
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
				if ( child.children?.length ) {
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

	return (
		<div
			{ ...focusOutside }
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
				isExpanded={ isExpanded }
				onFocus={ () => {
					setIsExpanded( true );
				} }
				instanceId={ instanceId }
				placeholder={ placeholder }
				label={ label }
				onTagsChange={ handleTagsChange }
			/>
			{ isExpanded && (
				<div
					className="woocommerce-tree-select-control__listbox"
					role="tree"
					tabIndex="-1"
				>
					<Options
						options={ optionsRef }
						value={ value }
						onChange={ handleOptionsChange }
					/>
				</div>
			) }
		</div>
	);
};

export default TreeSelectControl;
