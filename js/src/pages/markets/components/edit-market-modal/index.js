/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppModal from '~/components/app-modal';
import MarketForm from '../market-form';
import MarketFields from '../market-fields';
import ModalFooter from './modal-footer';
import isPrimaryMarket from '../../utils/isPrimaryMarket';
import './index.scss';

/**
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Modal component for editing an existing market.
 *
 * @param {Object} props
 * @param {{ id: string, label: string }} props.market The market being edited. Its `countries`
 *                                                      (for the primary market) is already the
 *                                                      backend-filtered list from `mc/markets`
 *                                                      and must be used as-is.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const EditMarketModal = ( { market, onRequestClose } ) => {
	const appModalTitle = isPrimaryMarket( market )
		? __( 'Edit primary market', 'google-listings-and-ads' )
		: sprintf(
				/* translators: %s is the name of the market being edited, e.g. "Europe". */
				__( 'Edit %s', 'google-listings-and-ads' ),
				market.label
		  );

	return (
		<AppModal
			title={ appModalTitle }
			onRequestClose={ onRequestClose }
			className="gla-edit-market-modal"
		>
			<MarketForm initialMarket={ market } onSubmit={ onRequestClose }>
				<MarketFields />
				<ModalFooter onCancel={ onRequestClose } />
			</MarketForm>
		</AppModal>
	);
};

export default EditMarketModal;
