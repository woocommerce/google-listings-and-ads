/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppInputControl from '../../../../../components/app-input-control';
import VerticalGapLayout from '../../components/vertical-gap-layout';
import './index.scss';

const formKeys = {
	freeShipping: 'shippingRateOption-freeShipping',
	priceOver: 'shippingRateOption-freeShipping-priceOver',
};

const SimpleShippingRateSetup = ( props ) => {
	const {
		formProps: { getInputProps, values },
	} = props;

	return (
		<div className="gla-simple-shipping-rate-setup">
			<VerticalGapLayout>
				<CheckboxControl
					label={ __(
						'I also offer free shipping for all countries for products over a certain price.',
						'google-listings-and-ads'
					) }
					{ ...getInputProps( formKeys.freeShipping ) }
				/>
				{ values[ formKeys.freeShipping ] && (
					<div className="price-input">
						<AppInputControl
							label={ __(
								'I offer free shipping for products priced over',
								'google-listings-and-ads'
							) }
							suffix="USD"
							{ ...getInputProps( formKeys.priceOver ) }
						/>
					</div>
				) }
			</VerticalGapLayout>
		</div>
	);
};

export default SimpleShippingRateSetup;
