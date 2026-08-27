/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, createInterpolateElement } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';
import { update as updateIcon } from '@wordpress/icons';
import { getPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useStoreAddress from '~/hooks/useStoreAddress';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import ValidationErrors from '~/components/validation-errors';
import ContactInformationPreviewCard from './contact-information-preview-card';
import TrackableLink from '~/components/trackable-link';
import mapStoreAddressErrors from './mapStoreAddressErrors';
import { recordGlaEvent } from '~/utils/tracks';
import './store-address-card.scss';

/**
 * Triggered when store address "Edit in WooCommerce Settings" button is clicked.
 * Before `1.5.0` it was called `edit_mc_store_address`.
 *
 * @event gla_edit_wc_store_address
 * @property {string} path The path used in the page from which the link was clicked, e.g. `"/google/settings"`.
 * @property {string|undefined} [subpath] The subpath used in the page, e.g. `"/edit-store-address"` or `undefined` when there is no subpath.
 */

/**
 * Track how many times and what fields the store address is having validation errors.
 *
 * @event gla_wc_store_address_validation
 * @property {string} path The path used in the page from which the event tracking was sent, e.g. `"/google/setup-mc"` or `"/google/settings"`.
 * @property {string|undefined} [subpath] The subpath used in the page, e.g. `"/edit-store-address"` or `undefined` when there is no subpath.
 * @property {string} country_code The country code of store address, e.g. `"US"`.
 * @property {string} missing_fields The string of the missing required fields of store address separated by comma, e.g. `"city,postcode"`.
 */

/**
 * Renders a component with a given store address.
 *
 * @fires gla_edit_wc_store_address Whenever "Edit in WooCommerce Settings" button is clicked.
 * @fires gla_wc_store_address_validation Whenever the new store address data is fetched after clicking "Update store address" button.
 * @return {JSX.Element} Filled AccountCard component.
 */
const StoreAddressCard = () => {
	const { loaded, data, refetch } = useStoreAddress();
	const { isAddressFilled } = data;
	const path = getPath();
	const { subpath } = getQuery();

	const refetchedCallbackRef = useRef( null );

	if ( loaded && refetchedCallbackRef.current ) {
		refetchedCallbackRef.current( data );
		refetchedCallbackRef.current = null;
	}

	const handleRefreshClick = () => {
		refetch();

		refetchedCallbackRef.current = ( storeAddress ) => {
			const eventProps = {
				path,
				subpath,
				country_code: storeAddress.countryCode,
				missing_fields: storeAddress.missingRequiredFields.join( ',' ),
			};

			recordGlaEvent( 'gla_wc_store_address_validation', eventProps );
		};
	};

	const refreshButton = (
		<AppButton
			disabled={ ! loaded }
			icon={ updateIcon }
			iconPosition="right"
			iconSize={ 20 }
			onClick={ handleRefreshClick }
			text={ __( 'Update store address', 'google-listings-and-ads' ) }
			isSecondary
		/>
	);

	const settingsLink = (
		<TrackableLink
			eventName="gla_edit_wc_store_address"
			eventProps={ { path, subpath } }
			href="admin.php?page=wc-settings"
			target="_blank"
			type="external"
		/>
	);

	let addressContent = <Spinner />;

	if ( loaded ) {
		const { address, address2, city, state, country, postcode } = data;
		const stateAndCountry = state ? `${ state } - ${ country }` : country;

		const rest = [ city, stateAndCountry, postcode ]
			.filter( Boolean )
			.join( ', ' );

		addressContent = (
			<>
				{ address && <div>{ address }</div> }
				{ address2 && <div>{ address2 }</div> }
				<div>{ rest }</div>
			</>
		);
	}

	const description = (
		<p>
			{ isAddressFilled
				? createInterpolateElement(
						__(
							'We’re using your store address for Google verification. This information won’t be public. Edit in <link>WooCommerce settings</link> if needed and update to review the changes.',
							'google-listings-and-ads'
						),
						{
							link: settingsLink,
						}
				  )
				: createInterpolateElement(
						__(
							'Your store address is required by Google for verification. This information won’t be public. Complete that in <link>WooCommerce settings</link> and update to review the changes.',
							'google-listings-and-ads'
						),
						{
							link: settingsLink,
						}
				  ) }
		</p>
	);

	const detail = (
		<>
			{ addressContent }
			{ ! isAddressFilled && (
				<ValidationErrors messages={ mapStoreAddressErrors( data ) } />
			) }
		</>
	);

	return (
		<AccountCard
			alignIcon="top"
			alignIndicator="top"
			appearance={ APPEARANCE.ADDRESS }
			className="gla-store-address-card"
			description={ description }
			detail={ detail }
			indicator={ refreshButton }
		/>
	);
};

export default StoreAddressCard;

/**
 * Trigger when store address edit button is clicked.
 * Before `1.5.0` this name was used for tracking clicking "Edit in settings" to edit the WC address. As of `>1.5.0`, that event is now tracked as `edit_wc_store_address`.
 *
 * @event gla_edit_mc_store_address
 * @property {string} path The path used in the page from which the link was clicked, e.g. `"/google/settings"`.
 * @property {string|undefined} [subpath] The subpath used in the page, e.g. `"/edit-store-address"` or `undefined` when there is no subpath.
 */

/**
 * Renders a component with the store address.
 * In preview mode, meaning there will be no refresh button, just the edit link.
 *
 * @fires gla_edit_mc_store_address Whenever "Edit" is clicked.
 *
 * @param {Object} props React props
 * @param {string} props.editHref URL where Edit button should point to.
 * @param {JSX.Element} props.learnMore Link to be shown at the end of missing data message.
 * @return {JSX.Element} Filled AccountCard component.
 */
export function StoreAddressCardPreview( { editHref, learnMore } ) {
	const { loaded, data } = useStoreAddress( 'mc' );
	let content, warning;

	if ( loaded ) {
		const {
			isAddressFilled,
			isMCAddressDifferent,
			address,
			address2,
			city,
			state,
			country,
			postcode,
		} = data;
		const stateAndCountry = state ? `${ state } - ${ country }` : country;

		if ( isAddressFilled && ! isMCAddressDifferent ) {
			content = [ address, address2, city, stateAndCountry, postcode ]
				.filter( Boolean )
				.join( ', ' );
		} else {
			warning = __(
				'Please add your store address',
				'google-listings-and-ads'
			);
			content = (
				<>
					{ __(
						'Google requires the store address for all stores using Google Merchant Center. ',
						'google-listings-and-ads'
					) }
					{ learnMore }
				</>
			);
		}
	}

	return (
		<ContactInformationPreviewCard
			appearance={ APPEARANCE.ADDRESS }
			content={ content }
			editEventName="gla_edit_mc_store_address"
			editHref={ editHref }
			loading={ ! loaded }
			warning={ warning }
		></ContactInformationPreviewCard>
	);
}
