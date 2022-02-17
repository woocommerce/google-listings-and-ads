/**
 * External dependencies
 */
import { useMemo, useState } from '@wordpress/element';
import classnames from 'classnames';
// eslint-disable-next-line import/no-extraneous-dependencies,@woocommerce/dependency-group,@wordpress/no-unsafe-wp-apis
import { __experimentalUseFocusOutside as useFocusOutside } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import Control from './control';
import List from './list';
import './index.scss';

/**
 * The Option type Object. This is how we send the options to the selector.
 *
 * @typedef { Object } Option
 * @property {string} id The unique ID for the option
 * @property {string} name The name for the option
 * @property {Option[]} [children] The children Option objects
 */

/**
 * Renders a component with a searchable control, tags and a tree selector.
 *
 * @param {Object} props Component props.
 * @param {string} props.id Component id
 * @param {string} props.label Label for the component
 * @param {string} props.placeholder Placeholder for the search control input
 * @param {string} props.className The class name for this component
 * @param {boolean} props.disabled Disables the component
 * @param {Option[]} props.options Options to show in the component
 * @param {{string}[]} props.value Selected values
 * @param {Function} props.onChange Callback when the selector changes
 *
 * @return {JSX.Element|null} The component
 */
const TreeSelectControl = ( {
	id,
	label,
	placeholder,
	className,
	disabled,
	options = [],
	value = [],
	onChange = () => {},
} ) => {
	const [ isExpanded, setIsExpanded ] = useState( false );
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
		options.forEach( ( option ) => {
			option.children.forEach( ( child ) => {
				repository[ child.id ] = { ...child, parent: option.id };
			} );
		} );

		return repository;
	}, [ options ] );

	if ( ! options.length ) {
		return null;
	}

	/**
	 * Get formatted Tags from the selected values.
	 *
	 * @return {{name: {string}, id: {string}}[]} An array of Tags
	 */
	const getTags = () => {
		return value.map( ( key ) => {
			const option = optionsRepository[ key ];
			return { id: key, name: option.name };
		} );
	};

	/**
	 * Handles a change on the Tree List of options. Could be a click on a parent option
	 * or a child option
	 *
	 * @param {boolean} checked Indicates if the item should be checked
	 * @param {Option} option The option to change
	 */
	const handleListChange = ( checked, option ) => {
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
		const idPosition = value.indexOf( option.id );
		const newValue = [ ...value ];

		if ( ! checked ) {
			newValue.splice( idPosition, 1 );
		} else {
			newValue.push( option.id );
		}

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

		option.children.forEach( ( el ) => {
			const childIdPosition = newValue.indexOf( el.id );

			if ( ! checked && childIdPosition >= 0 ) {
				newValue.splice( childIdPosition, 1 );
			}

			if ( checked && childIdPosition < 0 ) {
				newValue.push( el.id );
			}
		} );

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
					htmlFor={ `woocommerce-tree-select-control-${ id }__control-input` }
					className="woocommerce-tree-select-control__label"
				>
					{ label }
				</label>
			) }

			<Control
				disabled={ disabled }
				tags={ getTags() }
				isExpanded={ isExpanded }
				setExpanded={ setIsExpanded }
				instanceId={ id }
				placeholder={ placeholder }
				label={ label }
				onTagsChange={ handleTagsChange }
			/>
			{ isExpanded && (
				<List
					options={ options }
					selected={ value }
					onChange={ handleListChange }
				/>
			) }
		</div>
	);
};

export default TreeSelectControl;
