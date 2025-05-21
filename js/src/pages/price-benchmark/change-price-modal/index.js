/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

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
} from '../constants';
import MetricValue from './metric-value';
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import PriceInputFooter from './price-input-footer';
import AppSpinner from '~/components/app-spinner';
import Badge from '~/components/badge';
import usePriceBenchmarkSuggestions from '~/hooks/usePriceBenchmarkSuggestions';
import useProduct from '~/hooks/useProduct';
import './index.scss';

/**
 * ChangePriceModal component.
 *
 * This component renders a modal for changing the price of a product. It displays
 * product details, price metrics, and allows the user to input a new price.
 *
 * @param {Object} props - Component properties.
 * @param {number|string} props.productId - The ID of the product to change the price for.
 * @param {Function} props.onRequestClose - Callback function to handle closing the modal.
 * @param {Function} props.onPriceChange - Callback function to handle price change events.
 *
 * @return {JSX.Element} The rendered ChangePriceModal component.
 */
const ChangePriceModal = ( { productId, onRequestClose, onPriceChange } ) => {
	const {
		product: productDetails,
		hasFinishedResolution: hasResolvedProduct,
	} = useProduct( productId );
	const { data, hasFinishedResolution } =
		usePriceBenchmarkSuggestions( productId );

	const appModalProps = {
		title: __( 'Change Price', 'google-listings-and-ads' ),
		onRequestClose,
		className: 'gla-change-price-modal',
	};
	console.log(
		'ChangePriceModal',
		data,
		hasResolvedProduct,
		hasFinishedResolution
	);

	if (
		! hasResolvedProduct ||
		! hasFinishedResolution ||
		data === undefined
	) {
		return (
			<AppModal { ...appModalProps }>
				<AppSpinner />
			</AppModal>
		);
	}

	if ( ! productDetails && hasResolvedProduct ) {
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
	console.log( 'data', data );

	const {
		effectiveness,
		regular_price: regularPrice,
		price_on_google: priceOnGoogle,
		price_gap: priceGap,
		suggested_price: suggestedPrice,
		clicks,
		conversions,
		predicted_clicks_change: predictedClicksChange,
		predicted_conversions_change: predictedConversionsChange,
		product: { id, title, thumbnail },
	} = data || {};

	const salesPrice = Number.parseFloat( productDetails.sale_price );
	const isOnSale = productDetails.on_sale;

	return (
		<AppModal
			{ ...appModalProps }
			buttons={ [
				<PriceInputFooter
					onPriceChange={ onPriceChange }
					productId={ id }
					key="price-input-footer"
					suggestedPrice={ suggestedPrice }
					onSale={ isOnSale }
					salesPrice={ salesPrice }
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
							value={ regularPrice }
							type={ METRIC_TYPE_PRICE }
						/>

						<MetricValue
							labelKey={ LABEL_AVG_PRICE_ON_GOOGLE }
							value={ priceOnGoogle }
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
							<Badge intent="warning">
								{ __(
									'Product is currently on sale',
									'google-listings-and-ads'
								) }
							</Badge>
						) }
					</div>
				</div>
			</div>
		</AppModal>
	);
};

export default ChangePriceModal;
