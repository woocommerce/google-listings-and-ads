// Copied from WooCommerce core and simplified to leave only the needed parts.
// Ref: https://github.com/woocommerce/woocommerce/blob/release/8.4/packages/js/product-editor/src/components/label/label.tsx

/**
 * External dependencies
 */
import { Icon, help as helpIcon } from '@wordpress/icons';
import { Tooltip } from '@wordpress/components';

/**
 * Renders a label with Tooltip for product custom block field.
 *
 * @param {Object} props React props.
 * @param {string} [props.label] The label content.
 * @param {string} [props.tooltip] The content to be shown when hovering on the tooltip.
 */
export default function Label( { label, tooltip } ) {
	return (
		<div className="woocommerce-product-form-label__label">
			{ label }
			{ tooltip && (
				<Tooltip
					text={ <span>{ tooltip }</span> }
					position="top center"
					className="woocommerce-product-form-label__tooltip"
					delay={ 0 }
				>
					<span className="woocommerce-product-form-label__icon">
						<Icon icon={ helpIcon } size={ 18 } fill="#949494" />
					</span>
				</Tooltip>
			) }
		</div>
	);
}
