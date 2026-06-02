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
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Modal component for editing an existing market.
 *
 * @param {Object} props
 * @param {{ id: string, label: string }} props.market The market being edited.
 * @param {TargetAudienceData} props.targetAudience Target audience value data to initialize the form with.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const EditMarketModal = ( { market, targetAudience, onRequestClose } ) => {
	const appModalTitle = isPrimaryMarket( market )
		? __( 'Edit primary market', 'google-listings-and-ads' )
		: sprintf(
				/* translators: %s is the name of the market being edited, e.g. "Europe". */
				__( 'Edit %s', 'google-listings-and-ads' ),
				market.label
		  );

	// `targetAudience.countries` is the authoritative list for the primary
	// market and may have been refreshed since `market` was read. Only override
	// for the primary — secondary markets carry their own single-country
	// `countries` array that must not be replaced with the primary's audience.
	const initialMarket = isPrimaryMarket( market )
		? { ...market, countries: targetAudience.countries }
		: market;

	return (
		<AppModal
			title={ appModalTitle }
			onRequestClose={ onRequestClose }
			className="gla-edit-market-modal"
		>
			<MarketForm
				initialMarket={ initialMarket }
				onSubmit={ onRequestClose }
			>
				<MarketFields />
				<ModalFooter onCancel={ onRequestClose } />
			</MarketForm>
		</AppModal>
	);
};

export default EditMarketModal;
