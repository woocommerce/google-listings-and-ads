/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import classnames from 'classnames';

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
	const [ isExpanded, setIsExpanded ] = useState( true );

	if ( ! options.length ) {
		return null;
	}

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
		}

		onChange( Array.from( new Set( newValue ) ) );
	};

	return (
		<div
			className={ classnames(
				'woocommerce-tree-select-control',
				className
			) }
		>
			<Control
				disabled={ disabled }
				hasTags
				isExpanded={ isExpanded }
				setExpanded={ setIsExpanded }
				instanceId={ props.id }
				placeholder={ placeholder }
				label={ label }
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
