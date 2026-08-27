/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AddMarketButton from '../add-market-button';
import { getShippingRateLabel } from '../../utils/getShippingRateLabel';
import './index.scss';

/**
 * Header for the Markets dashboard.
 *
 * @param {Object} props
 * @param {string} [props.shippingRate] One of the values defined in `SHIPPING_RATE_METHOD`.
 */
const MarketsHeader = ( { shippingRate } ) => {
	const description = getShippingRateLabel( shippingRate );

	return (
		<Flex
			align="center"
			className="gla-markets-header"
			justify="space-between"
		>
			<FlexBlock>
				<h1 className="gla-markets-header__title">
					{ __( 'Markets', 'google-listings-and-ads' ) }
				</h1>
				<p className="gla-markets-header__description">
					{ description ?? (
						<span
							aria-busy="true"
							aria-label={ __(
								'Loading…',
								'google-listings-and-ads'
							) }
							className="gla-markets-header__description-placeholder"
						/>
					) }
				</p>
			</FlexBlock>
			<FlexItem>
				<AddMarketButton />
			</FlexItem>
		</Flex>
	);
};

export default MarketsHeader;
