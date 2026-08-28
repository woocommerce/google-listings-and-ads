/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import { useAdaptiveFormInputProps } from '~/components/adaptive-form';
import MinimumOrderCard from './minimum-order-card';

const OrderValueConditionSection = () => {
	const inputProps = useAdaptiveFormInputProps(
		'shipping_country_rates',
		'free_shipping_threshold'
	);

	return (
		<Section
			description={
				<div>
					<p> { __( 'Optional', 'google-listings-and-ads' ) } </p>
				</div>
			}
			title={ __( 'Order value condition', 'google-listings-and-ads' ) }
		>
			<MinimumOrderCard { ...inputProps } />
		</Section>
	);
};

export default OrderValueConditionSection;
