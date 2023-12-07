/**
 * External dependencies
 */
import { date as formatDate, getDate } from '@wordpress/date';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { useState } from '@wordpress/element';
import {
	__experimentalUseProductEntityProp as useProductEntityProp,
	__experimentalTextControl as TextControl,
} from '@woocommerce/product-editor';
import { Flex, FlexBlock } from '@wordpress/components';

export default function Edit( { attributes, context } ) {
	const blockProps = useWooBlockProps( attributes );
	const [ value, setValue ] = useProductEntityProp( attributes.property, {
		postType: context.postType,
		fallbackValue: '',
	} );

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

		if ( nextDate && nextTime ) {
			// The date and time values are strings presented in the site timezone.
			// Normalize them to date string in UTC timezone in ISO 8601 format.
			const dateInSiteTimezone = getDate( `${ nextDate }T${ nextTime }` );
			nextValue = formatDate( 'c', dateInSiteTimezone, 'UTC' );
		}

		setDate( nextDate );
		setTime( nextTime );

		if ( value !== nextValue ) {
			setValue( nextValue );
		}
	};

	return (
		<div { ...blockProps }>
			<Flex align="flex-end">
				<FlexBlock>
					<TextControl
						type="date"
						pattern="\d{4}-\d{2}-\d{2}"
						label={ attributes.label }
						tooltip={ attributes.tooltip }
						value={ date }
						onChange={ ( nextDate ) =>
							setNextValue( nextDate, time )
						}
					/>
				</FlexBlock>
				<FlexBlock>
					<TextControl
						type="time"
						pattern="[0-9]{2}:[0-9]{2}"
						value={ time }
						onChange={ ( nextTime ) =>
							setNextValue( date, nextTime )
						}
					/>
				</FlexBlock>
			</Flex>
		</div>
	);
}
