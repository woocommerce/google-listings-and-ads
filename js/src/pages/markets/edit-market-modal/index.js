/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../constants';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import EditPrimaryAudience from './edit-primary-audience';
import MarketNotice from '../market-notice';
import MarketForm from '../market-form';

const CONTEXT = 'edit_market_modal';

/**
 * @typedef {import('~/data/actions').TargetAudienceData } TargetAudienceData
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Event fired when the "Cancel" button in the EditMarketModal is clicked.
 * @event gla_cancel_button_clicked
 * @property {string} context The context in which the cancel button click happened, e.g. "edit_market_modal".
 */

/**
 * Event fired when the "Save" button in the EditMarketModal is clicked.
 * @event gla_save_button_clicked
 * @property {string} context The context in which the save button click happened, e.g. "edit_market_modal".
 */

/**
 * Placeholder for the Edit Market modal.
 *
 * The follow-up task will replace this with a real form. For now, the modal
 * renders the selected market's name and a Close button so the open/close
 * wiring from `MarketDataViews` can be reviewed end-to-end.
 *
 * @fires gla_cancel_button_clicked when the cancel button is clicked with context of "edit_market_modal"
 * @fires gla_save_button_clicked when the save button is clicked with context of "edit_market_modal"
 *
 * @param {Object} props
 * @param {{ id: string, label: string }} props.market The market being edited.
 * @param {TargetAudienceData} props.targetAudience Target audience value data to initialize the form with.
 * @param {() => void} props.onRequestClose Called when the user closes the modal.
 */
const EditMarketModal = ( { market, targetAudience, onRequestClose } ) => {
	const { id } = market;
	const isPrimaryMarket = id === PRIMARY_MARKET_ID;

	const appModalTitle = isPrimaryMarket
		? __( 'Edit primary market', 'google-listings-and-ads' )
		: __( 'Edit market', 'google-listings-and-ads' );

	let initialValues = {};
	if ( isPrimaryMarket ) {
		initialValues = {
			countries: targetAudience.countries || [],
		};
	}

	return (
		<MarketForm
			initialMarket={ {
				id,
				...initialValues,
			} }
			onSubmit={ onRequestClose }
		>
			{ ( formContext ) => {
				const {
					isValidForm,
					handleSubmit: handleSave,
					isDirty,
					adapter,
				} = formContext;
				const { isSaving } = adapter;

				return (
					<AppModal
						title={ appModalTitle }
						onRequestClose={ onRequestClose }
						overflow="visible"
						buttons={ [
							<AppButton
								key="close"
								variant="tertiary"
								onClick={ onRequestClose }
								disabled={ isSaving }
								eventName="gla_cancel_button_clicked"
								eventProps={ {
									context: CONTEXT,
								} }
							>
								{ __( 'Cancel', 'google-listings-and-ads' ) }
							</AppButton>,
							<AppButton
								key="save"
								variant="primary"
								onClick={ handleSave }
								disabled={ ! isValidForm || ! isDirty }
								loading={ isSaving }
								eventName="gla_save_button_clicked"
								eventProps={ {
									context: CONTEXT,
								} }
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</AppButton>,
						] }
					>
						{ isPrimaryMarket && <EditPrimaryAudience /> }

						<MarketNotice context="edit-market-modal" />
					</AppModal>
				);
			} }
		</MarketForm>
	);
};

export default EditMarketModal;
