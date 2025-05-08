/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import ChangePriceModal from './change-price-modal';

/**
 * ChangePrice component allows users to update the price of a product.
 * It provides a button to open a modal where the price can be changed.
 *
 * @param {Object} props - Component properties.
 * @param {number} props.productId - The ID of the product whose price is to be changed.
 * @return {JSX.Element|null} The rendered component or null if no productId is provided.
 */
const ChangePrice = ( { productId } ) => {
	const { receivePriceBenchmarkSuggestionsRegularPrice } = useAppDispatch();
	const [ isOpen, setIsOpen ] = useState( false );

	const handleOnRequestClose = () => {
		setIsOpen( false );
	};

	const handleOnPriceChange = useCallback(
		( updatedProductId, newPrice ) => {
			receivePriceBenchmarkSuggestionsRegularPrice(
				updatedProductId,
				newPrice
			);

			handleOnRequestClose();
		},
		[ receivePriceBenchmarkSuggestionsRegularPrice ]
	);

	if ( ! productId ) {
		return null;
	}

	const openModal = () => {
		setIsOpen( true );
	};

	return (
		<>
			<AppButton onClick={ openModal } variant="tertiary" size="compact">
				{ __( 'Change price', 'google-listings-and-ads' ) }
			</AppButton>

			{ isOpen && (
				<ChangePriceModal
					productId={ productId }
					onPriceChange={ handleOnPriceChange }
					onRequestClose={ handleOnRequestClose }
				/>
			) }
		</>
	);
};

export default ChangePrice;
