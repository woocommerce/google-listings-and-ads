/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import AddMarket from '../add-market';
import EditMarketModal from '../edit-market-modal';
import { getShippingRateLabel } from '../../utils';
import './index.scss';

const DUMMY_EDIT_MARKET = {
	id: 'dummy',
	label: __( 'Dummy market', 'google-listings-and-ads' ),
};

/**
 * Header for the Markets dashboard.
 *
 * @param {Object} props
 * @param {string} [props.shippingRate] One of the values defined in `SHIPPING_RATE_METHOD`.
 */
const MarketsHeader = ( { shippingRate } ) => {
	const description = getShippingRateLabel( shippingRate );
	const [ isDummyEditOpen, setIsDummyEditOpen ] = useState( false );
	const openDummyEdit = useCallback( () => setIsDummyEditOpen( true ), [] );
	const closeDummyEdit = useCallback( () => setIsDummyEditOpen( false ), [] );

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
				<p className="gla-markets-header__description">
					{ description ?? (
						<span
							className="gla-markets-header__description-placeholder"
							aria-busy="true"
							title={ __(
								'Loading…',
								'google-listings-and-ads'
							) }
						/>
					) }
				</p>
			</FlexBlock>
			<FlexItem>
				<>
					<Flex gap={ 2 } justify="flex-end">
						<AppButton variant="tertiary" onClick={ openDummyEdit }>
							{ __( 'Open edit modal', 'google-listings-and-ads' ) }
						</AppButton>
						<AddMarket />
					</Flex>
					{ isDummyEditOpen && (
						<EditMarketModal
							market={ DUMMY_EDIT_MARKET }
							onRequestClose={ closeDummyEdit }
						/>
					) }
				</>
			</FlexItem>
		</Flex>
	);
};

export default MarketsHeader;
