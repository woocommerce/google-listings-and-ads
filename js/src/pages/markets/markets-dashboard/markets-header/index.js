/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AddMarket from '../add-market';
import { getShippingRateLabel } from '../../utils';
import './index.scss';

/**
 * Header for the Markets dashboard.
 *
 * @param {Object} props
 * @param {string} [props.shippingRate] One of the values defined in `SHIPPING_RATE_OPTION`.
 */
const MarketsHeader = ( { shippingRate } ) => {
	const description = getShippingRateLabel( shippingRate );

	return (
		<Flex
			className="gla-markets-header"
			align="center"
			justify="space-between"
		>
			<FlexBlock>
				<h1 className="gla-markets-header__title">
					{ __( 'Markets', 'google-listings-and-ads' ) }
				</h1>
				{ description && (
					<p className="gla-markets-header__description">
						{ description }
					</p>
				) }
			</FlexBlock>
			<FlexItem>
				<AddMarket />
			</FlexItem>
		</Flex>
	);
};

export default MarketsHeader;
