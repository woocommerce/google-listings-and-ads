/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import GridiconShipping from 'gridicons/dist/shipping';

/**
 * Internal dependencies
 */
import useAdsCurrency from '~/hooks/useAdsCurrency';
import './google-shipping-preview.scss';

/**
 * Renders a preview of how the free shipping offer will look on Google.
 *
 * @param {Object} props React props.
 * @param {number} props.threshold The order value threshold for free shipping.
 * @return {JSX.Element|null} The rendered component or null if no threshold is provided.
 */
const GoogleShippingPreview = ( { threshold } ) => {
	const { formatAmount } = useAdsCurrency();

	if ( ! threshold ) {
		return null;
	}

	return (
		<div className="gla-google-shipping-preview">
			<Flex justify="flex-start">
				<FlexItem>
					<p>
						<em>
							{ __(
								'Example of what your customers will see on Google:',
								'google-listings-and-ads'
							) }
						</em>
					</p>
				</FlexItem>
				<FlexBlock>
					<Flex gap={ 1 }>
						<FlexItem>
							<GridiconShipping />
						</FlexItem>
						<FlexBlock>
							<p>
								<strong>
									{ sprintf(
										/* translators: %s is the order value threshold for free shipping. */
										__(
											'Ships free over %s',
											'google-listings-and-ads'
										),
										formatAmount( threshold )
									) }
								</strong>
							</p>
						</FlexBlock>
					</Flex>
				</FlexBlock>
			</Flex>
		</div>
	);
};

export default GoogleShippingPreview;
