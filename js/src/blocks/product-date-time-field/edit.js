/**
 * External dependencies
 */
import { date as formatDate, getDate } from '@wordpress/date';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { useState, useRef } from '@wordpress/element';
import {
	__experimentalUseProductEntityProp as useProductEntityProp,
	__experimentalTextControl as TextControl,
	useValidation,
} from '@woocommerce/product-editor';
import { Flex, FlexBlock } from '@wordpress/components';
import { isWcVersion } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import styles from './editor.module.scss';

/**
 * @typedef {import('../types.js').ProductEditorBlockContext} ProductEditorBlockContext
 * @typedef {import('../types.js').ProductBasicAttributes} ProductBasicAttributes
 */

async function resolveValidationMessage( inputRef, context ) {
	const input = inputRef.current;

	if ( ! input.validity.valid ) {
		if ( isWcVersion( '9.2.0', '>' ) ) {
			return input.validationMessage;
		}

		return {
			message: input.validationMessage,
			context,
		};
	}
}

/**
 * Custom block for editing a given product data with date and time fields.
 *
 * @param {Object} props React props.
 * @param {ProductBasicAttributes} props.attributes
 * @param {ProductEditorBlockContext} props.context
 * @param {string} props.clientId
 */
export default function Edit( { attributes, context, clientId } ) {
	const { property } = attributes;
	const blockProps = useWooBlockProps( attributes );
	const [ value, setValue ] = useProductEntityProp( property, {
		postType: context.postType,
		fallbackValue: '',
	} );
	const dateInputRef = useRef( null );
	const timeInputRef = useRef( null );

	// Refer to the "Derived value for initialization" in README for why these `useState`
	// can initialize directly.
	const [ date, setDate ] = useState( () =>
		value ? formatDate( 'Y-m-d', value ) : ''
	);
	const [ time, setTime ] = useState( () =>
		value ? formatDate( 'H:i', value ) : ''
	);

	const setNextValue = ( nextDate, nextTime ) => {
		let nextValue = '';

		// It allows `nextTime` to be an empty string and fall back to '00:00:00'.
		if ( nextDate ) {
			// The date and time values are strings presented in the site timezone.
			// Normalize them to date string in UTC timezone in ISO 8601 format.
			const isoDateString = `${ nextDate }T${ nextTime || '00:00:00' }`;
			const dateInSiteTimezone = getDate( isoDateString );
			nextValue = formatDate( 'c', dateInSiteTimezone, 'UTC' );
		}

		setDate( nextDate );
		setTime( nextTime );

		if ( value !== nextValue ) {
			setValue( nextValue );
		}
	};

	const dateValidation = useValidation( `${ property }-date`, () =>
		resolveValidationMessage( dateInputRef, clientId )
	);

	const timeValidation = useValidation( `${ property }-time`, () =>
		resolveValidationMessage( timeInputRef, clientId )
	);

	return (
		<div { ...blockProps }>
			<Flex align="flex-start">
				<FlexBlock>
					<TextControl
						ref={ dateInputRef }
						type="date"
						pattern="\d{4}-\d{2}-\d{2}"
						label={ attributes.label }
						tooltip={ attributes.tooltip }
						value={ date }
						error={ dateValidation.error }
						onChange={ ( nextDate ) =>
							setNextValue( nextDate, time )
						}
						onBlur={ dateValidation.validate }
					/>
				</FlexBlock>
				<FlexBlock>
					<TextControl
						// The invisible chars in the label and tooltip are to maintain the space between
						// the <label> and <input> as the same in the sibling <TextControl>. Also, it uses
						// CSS to keep its space but is invisible.
						className={ styles.invisibleLabelAndTooltip }
						label=" "
						tooltip="‎ "
						ref={ timeInputRef }
						type="time"
						pattern="[0-9]{2}:[0-9]{2}"
						value={ time }
						error={ timeValidation.error }
						onChange={ ( nextTime ) =>
							setNextValue( date, nextTime )
						}
						onBlur={ timeValidation.validate }
					/>
				</FlexBlock>
			</Flex>
		</div>
	);
}
