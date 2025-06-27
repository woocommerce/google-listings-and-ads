/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import {
	PRODUCTS_STORE_NAME,
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
} from '@woocommerce/data';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { PRICE_BENCHMARK_CHANGE_PRICE_MODAL_CONTEXT } from '../constants';
import { recordGlaEvent } from '~/utils/tracks';
import AppButton from '~/components/app-button';
import AppInputPriceControl from '~/components/app-input-price-control';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';

/**
 * @event gla_price_benchmarks_change_price_edited
 * @property {string} context The context in which the event is triggered.
 * @property {number} product_id The ID of the product whose price is being changed.
 * @property {number} previous_price The previous price of the product.
 * @property {number} recommended_price The recommended price for the product.
 * @property {number} changed_price The new price set for the product.
 * @property {string} currency The currency of the product price.
 * @property {string} gtin The global unique identifier (e.g., GTIN) for the product.
 */

/**
 * PriceInputFooter component.
 *
 * This component renders a footer for the price input modal, allowing users to
 * update the price of a product. It includes validation for the new price and
 * handles the update process.
 *
 * @fires gla_price_benchmarks_change_price_edited
 *
 * @param {Object} props - Component properties.
 * @param {number} props.productId - The ID of the product being updated.
 * @param {number} props.productPrice - The regular price of the product.
 * @param {number} props.suggestedPrice - The suggested price for the product.
 * @param {Function} props.onPriceChange - Callback function triggered after the price is successfully updated.
 * @param {Object} props.productDetails - The details object for the product, including type, sale price, etc.
 *
 * @return {JSX.Element} The rendered PriceInputFooter component.
 */
const PriceInputFooter = ( {
	productId,
	productPrice,
	suggestedPrice,
	onPriceChange,
	productDetails,
} ) => {
	const { formatAmount } = useAdsCurrency();
	const [ newPriceError, setNewPriceError ] = useState();
	const [ loading, setLoading ] = useState( false );
	const [ newPrice, setNewPrice ] = useState( 0 );
	const { googleAdsAccount } = useGoogleAdsAccount();
	const { updateProduct } = useDispatch( PRODUCTS_STORE_NAME );
	const { updateProductVariation } = useDispatch(
		EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
	);

	const {
		global_unique_id: globalUniqueId,
		on_sale: onSale,
		sale_price: salePrice,
		type,
		parent_id: parentId,
	} = productDetails;

	useEffect( () => {
		setNewPrice( suggestedPrice );
	}, [ suggestedPrice ] );

	const getInputError = useCallback( () => {
		const updatedPrice = Number.parseFloat( newPrice );

		const formattedSalesPrice = Number.parseFloat( salePrice );
		if (
			! isNaN( formattedSalesPrice ) &&
			formattedSalesPrice &&
			updatedPrice <= formattedSalesPrice &&
			onSale
		) {
			return sprintf(
				// Translators: %s is replaced with the sales price.
				__(
					'New price must be greater than the sales price (%s).',
					'google-listings-and-ads'
				),
				formatAmount( formattedSalesPrice )
			);
		}

		if ( updatedPrice < 0 ) {
			return __(
				'New price must be greater than or equals to zero.',
				'google-listings-and-ads'
			);
		}

		return null;
	}, [ newPrice, salePrice, formatAmount, onSale ] );

	const validatePrice = useCallback( () => {
		const error = getInputError();
		setNewPriceError( error );

		return error === null;
	}, [ getInputError ] );

	const handleOnPriceChange = useCallback( async () => {
		if ( ! validatePrice() ) {
			return;
		}

		setLoading( true );
		try {
			const updatePriceArg = {
				regular_price: `${ newPrice }`,
			};

			if ( type === 'variation' && parentId ) {
				await updateProductVariation(
					{
						product_id: parentId,
						id: productId,
					},
					updatePriceArg
				);
			} else {
				await updateProduct( productId, updatePriceArg );
			}

			recordGlaEvent( 'gla_price_benchmarks_change_price_edited', {
				context: PRICE_BENCHMARK_CHANGE_PRICE_MODAL_CONTEXT,
				product_id: productId,
				previous_price: productPrice,
				recommended_price: suggestedPrice,
				changed_price: newPrice,
				currency: googleAdsAccount?.currency,
				gtin: globalUniqueId,
			} );
		} catch ( error ) {
			setNewPriceError( error?.message );
			setLoading( false );
			return;
		}

		setLoading( false );
		onPriceChange( productId, newPrice );
	}, [
		newPrice,
		productId,
		onPriceChange,
		updateProduct,
		validatePrice,
		googleAdsAccount,
		productPrice,
		suggestedPrice,
		globalUniqueId,
		parentId,
		type,
		updateProductVariation,
	] );

	useEffect( () => {
		validatePrice();
	}, [ newPrice, validatePrice ] );

	const hasError = getInputError();

	return (
		<div className="gla-change-price-modal-price-input-footer">
			<AppInputPriceControl
				label={ __( 'New price', 'google-listings-and-ads' ) }
				suffix={ googleAdsAccount?.currency }
				value={ newPrice }
				onChange={ setNewPrice }
				className={ classnames(
					'gla-change-price-modal-price-input-footer__price',
					{
						'gla-change-price-modal-price-input-footer__price--error':
							newPriceError,
					}
				) }
				help={ newPriceError }
			/>

			<AppButton
				isPrimary
				onClick={ handleOnPriceChange }
				disabled={ hasError !== null }
				loading={ loading }
			>
				{ __( 'Change Price', 'google-listings-and-ads' ) }
			</AppButton>
		</div>
	);
};

export default PriceInputFooter;
