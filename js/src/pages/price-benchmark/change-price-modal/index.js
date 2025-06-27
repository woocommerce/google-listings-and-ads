/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { Notice } from '@wordpress/components';
import {
	useEffect,
	useCallback,
	createInterpolateElement,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	LABEL_AVG_PRICE_ON_GOOGLE,
	LABEL_CHANGE_EFFECTIVENESS,
	LABEL_CURRENT_CLICKS,
	LABEL_CURRENT_CONVERSIONS,
	LABEL_EXPECTED_UPLIFT_IN_CLICKS,
	LABEL_EXPECTED_UPLIFT_IN_CONVERSIONS,
	LABEL_PRICE_GAP_PERCENT,
	LABEL_REGULAR_PRICE,
	LABEL_SUGGESTED_PRICE,
	LABEL_SALES_PRICE,
	METRIC_TYPE_DELTA,
	METRIC_TYPE_EFFECTIVENESS,
	METRIC_TYPE_PERCENTAGE,
	METRIC_TYPE_PRICE,
	PRICE_BENCHMARK_CHANGE_PRICE_MODAL_CONTEXT,
} from '../constants';
import { recordGlaEvent } from '~/utils/tracks';
import MetricValue from './metric-value';
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import TrackableLink from '~/components/trackable-link';
import PriceInputFooter from './price-input-footer';
import AppSpinner from '~/components/app-spinner';
import usePriceBenchmarkSuggestions from '~/hooks/usePriceBenchmarkSuggestions';
import useProduct from '~/hooks/useProduct';
import useAdminUrl from '~/hooks/useAdminUrl';
import './index.scss';

/**
 * @event gla_modal_open
 * @property {string} context The context in which the event is triggered.
 * @property {number} product_id The ID of the product whose price is being changed.
 */

/**
 * @event gla_modal_closed
 * @property {string} context The context in which the event is triggered.
 * @property {number} product_id The ID of the product whose price is being changed.
 * @property {string} action The action taken to close the modal.
 */

/**
 * ChangePriceModal component.
 *
 * This component renders a modal for changing the price of a product. It displays
 * product details, price metrics, and allows the user to input a new price.
 *
 * @fires gla_modal_open with `{ context: 'price-benchmark-change-price-modal' }` and the product ID.
 * @fires gla_modal_closed with `{ context: 'price-benchmark-change-price-modal', action: 'close' }` and the product ID.
 *
 * @param {Object} props - Component properties.
 * @param {number|string} props.productId - The ID of the product to change the price for.
 * @param {Function} props.onRequestClose - Callback function to handle closing the modal.
 * @param {Function} props.onPriceChange - Callback function to handle price change events.
 *
 * @return {JSX.Element} The rendered ChangePriceModal component.
 */
const ChangePriceModal = ( { productId, onRequestClose, onPriceChange } ) => {
	const adminUrl = useAdminUrl();
	const {
		product: productDetails,
		hasFinishedResolution: hasResolvedProduct,
	} = useProduct( productId );
	const { data, hasFinishedResolution } = usePriceBenchmarkSuggestions( {
		product_id: productId,
	} );

	useEffect( () => {
		recordGlaEvent( 'gla_modal_open', {
			context: PRICE_BENCHMARK_CHANGE_PRICE_MODAL_CONTEXT,
			product_id: productId,
		} );
	}, [ productId ] );

	const handleOnRequestClose = useCallback( () => {
		recordGlaEvent( 'gla_modal_closed', {
			context: PRICE_BENCHMARK_CHANGE_PRICE_MODAL_CONTEXT,
			product_id: productId,
			action: 'close',
		} );

		onRequestClose();
	}, [ onRequestClose, productId ] );

	const appModalProps = {
		title: __( 'Change Price', 'google-listings-and-ads' ),
		onRequestClose: handleOnRequestClose,
		className: 'gla-change-price-modal',
	};

	if ( ! hasResolvedProduct || ! hasFinishedResolution ) {
		return (
			<AppModal { ...appModalProps }>
				<AppSpinner />
			</AppModal>
		);
	}

	if ( ( ! productDetails || ! data ) && hasResolvedProduct ) {
		return (
			<AppModal
				{ ...appModalProps }
				buttons={ [
					<AppButton key="close" isPrimary onClick={ onRequestClose }>
						{ __( 'Close', 'google-listings-and-ads' ) }
					</AppButton>,
				] }
			>
				<p>
					{ __( 'Product not found. ', 'google-listings-and-ads' ) }
				</p>
			</AppModal>
		);
	}

	const {
		effectiveness,
		product_price: productPrice,
		benchmark_price: benchmarkPrice,
		price_gap: priceGap,
		suggested_price: suggestedPrice,
		clicks,
		conversions,
		predicted_clicks_change: predictedClicksChange,
		predicted_conversions_change: predictedConversionsChange,
		product: { id, title, thumbnail },
	} = data;

	const salesPrice = Number.parseFloat( productDetails.sale_price );
	const isOnSale = productDetails.on_sale;
	const editProductUrl = addQueryArgs( `${ adminUrl }post.php`, {
		post: productId,
		action: 'edit',
	} );

	return (
		<AppModal
			{ ...appModalProps }
			buttons={ [
				<PriceInputFooter
					onPriceChange={ onPriceChange }
					productId={ id }
					key="price-input-footer"
					suggestedPrice={ suggestedPrice }
					productPrice={ productPrice }
					productDetails={ productDetails }
				/>,
			] }
		>
			<div className="gla-change-price-modal__content">
				<div className="gla-change-price-modal__product">
					{ thumbnail && (
						<div className="gla-change-price-modal__product-image">
							<img
								src={ thumbnail }
								alt={ __(
									'Product thumbnail',
									'google-listings-and-ads'
								) }
								width="156"
							/>
						</div>
					) }

					<div className="gla-change-price-modal__product-details">
						<p>
							<span>{ id }</span>
						</p>
						<p className="gla-change-price-modal__product-title">
							{ title }
						</p>
					</div>
				</div>

				<div className="gla-change-price-modal__metrics">
					<div className="gla-change-price-modal__metrics-grid">
						<MetricValue
							labelKey={ LABEL_CHANGE_EFFECTIVENESS }
							value={ effectiveness }
							type={ METRIC_TYPE_EFFECTIVENESS }
						/>

						<MetricValue
							labelKey={ LABEL_PRICE_GAP_PERCENT }
							value={ priceGap }
							type={ METRIC_TYPE_PERCENTAGE }
						/>

						<hr className="gla-change-price-modal__separator" />

						<MetricValue
							labelKey={ LABEL_REGULAR_PRICE }
							value={ productPrice }
							type={ METRIC_TYPE_PRICE }
						/>

						<MetricValue
							labelKey={ LABEL_AVG_PRICE_ON_GOOGLE }
							value={ benchmarkPrice }
							type={ METRIC_TYPE_PRICE }
						/>

						{ isOnSale && (
							<MetricValue
								labelKey={ LABEL_SALES_PRICE }
								value={ salesPrice }
								type={ METRIC_TYPE_PRICE }
								className="gla-change-price-modal__sales-price"
							/>
						) }

						<MetricValue
							labelKey={ LABEL_SUGGESTED_PRICE }
							value={ suggestedPrice }
							type={ METRIC_TYPE_PRICE }
						/>

						<hr className="gla-change-price-modal__separator" />

						<MetricValue
							labelKey={ LABEL_CURRENT_CLICKS }
							value={ clicks }
						/>

						<MetricValue
							labelKey={ LABEL_CURRENT_CONVERSIONS }
							value={ conversions }
						/>

						<MetricValue
							labelKey={ LABEL_EXPECTED_UPLIFT_IN_CLICKS }
							value={ predictedClicksChange }
							type={ METRIC_TYPE_DELTA }
						/>

						<MetricValue
							labelKey={ LABEL_EXPECTED_UPLIFT_IN_CONVERSIONS }
							value={ predictedConversionsChange }
							type={ METRIC_TYPE_DELTA }
						/>

						<hr className="gla-change-price-modal__separator" />

						{ isOnSale && (
							<Notice status="warning" isDismissible={ false }>
								{ createInterpolateElement(
									__(
										'This product is currently on sale. To change the sale price, go to the <link>Edit Product</link> page in WooCommerce.',
										'google-listings-and-ads'
									),
									{
										link: (
											<TrackableLink
												type="wp-admin"
												href={ editProductUrl }
												eventName="gla_price_benchmark_edit_product_link_click"
												eventProps={ {
													context:
														PRICE_BENCHMARK_CHANGE_PRICE_MODAL_CONTEXT,
												} }
											/>
										),
									}
								) }
							</Notice>
						) }
					</div>
				</div>
			</div>
		</AppModal>
	);
};

export default ChangePriceModal;
