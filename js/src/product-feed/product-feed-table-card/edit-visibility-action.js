/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Icon } from '@wordpress/components';
import { SelectControl } from '@woocommerce/components';
import { edit as editIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import AppTooltip from '.~/components/app-tooltip';

const tipText = __( 'Select channel visibility', 'google-listings-and-ads' );

const options = [
	{
		key: 0,
		value: true,
		label: __( 'Sync and show', 'google-listings-and-ads' ),
	},
	{
		key: 1,
		value: false,
		label: __( 'Don’t sync and show', 'google-listings-and-ads' ),
	},
];

function ConditionalTooltip( { withTooltip, children, ...restProps } ) {
	if ( withTooltip ) {
		return <AppTooltip children={ children } { ...restProps } />;
	}
	return children;
}

/**
 * @callback onActionClick
 * @param {boolean} visible `true` is "Sync and show" and `false` is "Don't sync and show".
 */

/**
 * Renders an action to select that how to edit the channel visibility of selected products.
 *
 * @param {Object} props React props
 * @param {number} props.selectedSize The size of selected products.
 * @param {onActionClick} props.onActionClick The callback func when clicking on the "Apply" button.
 */
export default function EditVisibilityAction( {
	selectedSize,
	onActionClick,
} ) {
	const [ selectedVisible, setSelectedVisible ] = useState( null );

	useEffect( () => {
		if ( selectedSize === 0 ) {
			setSelectedVisible( null );
		}
	}, [ selectedSize ] );

	if ( selectedSize === 0 ) {
		return (
			<AppTooltip
				position="top center"
				text={ __(
					'Select one or more products to bulk edit',
					'google-listings-and-ads'
				) }
			>
				<Icon icon={ editIcon } size={ 24 } />
			</AppTooltip>
		);
	}

	const handleClick = () => {
		const option = options.find( ( { key } ) => key === selectedVisible );
		onActionClick( option.value );
	};

	return (
		<>
			<SelectControl
				label={ tipText }
				options={ options }
				selected={ selectedVisible }
				onChange={ setSelectedVisible }
			/>
			<ConditionalTooltip
				withTooltip={ selectedVisible === null }
				position="top center"
				text={ tipText }
			>
				<AppButton
					isSecondary
					disabled={ selectedVisible === null }
					onClick={ handleClick }
				>
					{ sprintf(
						// translators: %d: number of selected products to edit channel visibility, with minimum value of 1.
						__( 'Apply to %d selected', 'google-listings-and-ads' ),
						selectedSize
					) }
				</AppButton>
			</ConditionalTooltip>
		</>
	);
}
