/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { recordGlaEvent } from '~/utils/tracks';
import {
	PRICE_BENCHMARK_CHANGE_PRICE_MODAL_CONTEXT,
	PRICE_BENCHMARK_SUGGESTIONS_CONTEXT,
} from './constants';
import AppButton from '~/components/app-button';
import ChangePriceModal from './change-price-modal';

/**
 * @event gla_price_benchmarks_change_price_clicked
 * @property {string} context The context in which the event is triggered.
 * @property {number} product_id The ID of the product whose price is being changed.
 */

/**
 * ChangePrice component allows users to update the price of a product.
 * It provides a button to open a modal where the price can be changed.
 *
 * @fires gla_price_benchmarks_change_price_clicked with `{ context: 'price-benchmark-change-price-modal' }` and the product ID.
 * @fires gla_modal_closed with `{ context: 'price-benchmark-change-price-modal', action: 'change-price' }`
 *
 * @param {Object} props - Component properties.
 * @param {number} props.productId - The ID of the product whose price is to be changed.
 * @return {JSX.Element|null} The rendered component or null if no productId is provided.
 */
const ChangePrice = ( { productId } ) => {
	const { receivePriceBenchmarkSuggestionsProductPrice } = useAppDispatch();
	const [ isOpen, setIsOpen ] = useState( false );

	const handleOnRequestClose = () => {
		setIsOpen( false );
	};

	const handleOnPriceChange = useCallback(
		( updatedProductId, newPrice ) => {
			receivePriceBenchmarkSuggestionsProductPrice(
				updatedProductId,
				newPrice
			);

			recordGlaEvent( 'gla_modal_closed', {
				context: PRICE_BENCHMARK_CHANGE_PRICE_MODAL_CONTEXT,
				action: 'change-price',
			} );

			handleOnRequestClose();
		},
		[ receivePriceBenchmarkSuggestionsProductPrice ]
	);

	if ( ! productId ) {
		return null;
	}

	const handleOnClick = () => {
		setIsOpen( true );
	};

	return (
		<>
			<AppButton
				onClick={ handleOnClick }
				variant="tertiary"
				size="compact"
				eventName="gla_price_benchmarks_change_price_clicked"
				eventProps={ {
					context: PRICE_BENCHMARK_SUGGESTIONS_CONTEXT,
					product_id: productId,
				} }
			>
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
