/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import { glaData } from '.~/constants';
import { getCreateCampaignUrl } from '.~/utils/urls';

/**
 * "Add paid campaign" button is clicked.
 *
 * @event gla_add_paid_campaign_clicked
 * @property {string} context Indicate the place where the button is located.
 * @property {string} href Indicate the destination where the users is directed to, e.g. `'/google/setup-ads'`.
 */

/**
 * Renders an AppButton with the text "Add Paid Campaign".
 * Clicking on the button will call `recordEvent` and
 * redirect to Setup MC or Create New Campaign depending on
 * whether the users have completed ads setup or not.
 *
 * `recordEvent` is called with a default eventName `'gla_add_paid_campaign_clicked'`,
 * and a default eventProps `{ context: '', href: newPath }`.
 * You can provide `eventProps` and it will be merged with the default eventProps.
 *
 * You should specify the context where this button is used, e.g. `eventProps={ { context: 'programs-table-card' } }`.
 *
 * @fires gla_add_paid_campaign_clicked with given props, when clicked.
 *
 * @param {Object} props Props
 * @param {string} [props.eventName='gla_add_paid_campaign_clicked'] eventName to be used when calling `recordEvent`.
 * @param {Object} [props.eventProps] eventProps to be used when calling `recordEvent`.
 * @param {string} [props.eventProps.context=''] Context to be used when calling `recordEvent`.
 * @param {string} [props.eventProps.href] Destination path. This would default to a path with
 * `'/google/setup-ads'` when users have not completed ads setup, or
 * `'/google/dashboard'` (with `subpath=/campaigns/create`) when users have completed ads setup.
 * @return {AppButton} AppButton
 */
const AddPaidCampaignButton = ( props ) => {
	const {
		eventName = 'gla_add_paid_campaign_clicked',
		eventProps,
		children,
		onClick = () => {},
		...rest
	} = props;
	const { adsSetupComplete } = glaData;
	const url = ! adsSetupComplete
		? getNewPath( {}, '/google/setup-ads', {} )
		: getCreateCampaignUrl();
	const defaultEventProps = { context: '', href: url };

	const handleClick = ( ...args ) => {
		recordEvent( eventName, {
			...defaultEventProps,
			...eventProps,
		} );
		getHistory().push( url );
		onClick( ...args );
	};

	return (
		<AppButton isSmall isSecondary onClick={ handleClick } { ...rest }>
			{ children || __( 'Add paid campaign', 'google-listings-and-ads' ) }
		</AppButton>
	);
};

export default AddPaidCampaignButton;
