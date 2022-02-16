/**
 * External dependencies
 */
import { useMemo, useState } from '@wordpress/element';
import classnames from 'classnames';
import { __experimentalUseFocusOutside as useFocusOutside } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import Control from './control';
import List from './list';
import './index.scss';

const TreeSelectControl = ( props ) => {
	const {
		className,
		options = [],
		disabled,
		value = [],
		label,
		placeholder,
		onChange = () => {},
	} = props;

	const [ isExpanded, setIsExpanded ] = useState( false );
	const focusOutside = useFocusOutside( () => {
		setIsExpanded( false );
	} );

	/**
	 * Optimizes the performance getting the tags info
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

	const getTags = () => {
		return value.map( ( id ) => {
			const option = optionsRepository[ id ];
			return { id, name: option.name };
		} );
	};

	const handleListChange = ( checked, option ) => {
		if ( option.children?.length ) {
			handleParentChange( checked, option );
		} else {
			handleSingleChange( checked, option );
		}
	};

	const handleSingleChange = ( checked, option ) => {
		const idPosition = value.indexOf( option.id );
		const newValue = [ ...value ];

		if ( ! checked && idPosition >= 0 ) {
			newValue.splice( idPosition, 1 );
		} else if ( checked && idPosition < 0 ) {
			newValue.push( option.id );
		}

		onChange( newValue );
	};

	const handleTagsChange = ( tags ) => {
		onChange( [ ...tags.map( ( el ) => el.id ) ] );
	};

	const handleParentChange = ( checked, option ) => {
		let newValue = [ ...value ];

		if ( ! checked ) {
			option.children.forEach( ( el ) => {
				const childIdPosition = newValue.indexOf( el.id );
				if ( childIdPosition >= 0 ) {
					newValue.splice( childIdPosition, 1 );
				}
			} );
		} else {
			newValue = [
				...newValue,
				...option.children.map( ( el ) => el.id ),
			];

			// unique values
			newValue = newValue.filter(
				( val, index, self ) => self.indexOf( val ) === index
			);
		}

		onChange( newValue );
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
					htmlFor={ `woocommerce-select-control-${ props.id }__control-input` }
					className="components-base-control__label"
				>
					{ label }
				</label>
			) }

			<Control
				disabled={ disabled }
				tags={ getTags() }
				isExpanded={ isExpanded }
				setExpanded={ setIsExpanded }
				instanceId={ props.id }
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
