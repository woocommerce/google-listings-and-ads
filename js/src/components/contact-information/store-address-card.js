/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CardDivider } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';
import { external as externalIcon } from '@wordpress/icons';
import GridiconRefresh from 'gridicons/dist/refresh';
import { getPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useStoreAddress from '.~/hooks/useStoreAddress';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import ContactInformationPreviewCard from './contact-information-preview-card';

/**
 * "Edit WC store address" Tracking event
 *
 * @event gla_edit_wc_store_address
 * @type {Object} TrackingEvent
 * @property {string} path A page from which the link was clicked.
 * @property {string|undefined} [subpath] A subpage from which the link was clicked.
 */

/**
 * Renders a component with a given store address.
 *
 * @fires gla_edit_wc_store_address Whenever "Edit in Settings" is clicked.
 *
 * @return {JSX.Element} Filled AccountCard component.
 */
export default function StoreAddressCard() {
	const { loaded, data, refetch } = useStoreAddress();
	const { subpath } = getQuery();
	const editButton = (
		<AppButton
			isSecondary
			icon={ externalIcon }
			iconSize={ 16 }
			iconPosition="right"
			target="_blank"
			href="admin.php?page=wc-settings"
			text={ __( 'Edit in Settings', 'google-listings-and-ads' ) }
			eventName="gla_edit_wc_store_address"
			eventProps={ { path: getPath(), subpath } }
		/>
	);

	let addressContent;
	let description = '';

	description = __(
		'Please confirm your store address for verification.',
		'google-listings-and-ads'
	);

	if ( loaded ) {
		const { address, address2, city, state, country, postcode } = data;
		const stateAndCountry = state ? `${ state } - ${ country }` : country;

		const rest = [ city, stateAndCountry, postcode ]
			.filter( Boolean )
			.join( ', ' );

		addressContent = (
			<div>
				<div>{ address }</div>
				{ address2 && <div>{ address2 }</div> }
				<div>{ rest }</div>
			</div>
		);
	} else {
		addressContent = <Spinner />;
	}

	return (
		<AccountCard
			appearance={ APPEARANCE.ADDRESS }
			description={ description }
			indicator={ editButton }
		>
			<CardDivider />
			<Section.Card.Body>
				<Subsection.Title>
					{ __( 'Store address', 'google-listings-and-ads' ) }
					<AppButton
						className="gla-store-address__refresh-button"
						icon={ GridiconRefresh }
						iconSize={ 16 }
						onClick={ refetch }
						disabled={ ! loaded }
					/>
				</Subsection.Title>
				{ addressContent }
			</Section.Card.Body>
		</AccountCard>
	);
}

/**
 * "Edit MC store address" Tracking event
 *
 * @event gla_edit_mc_store_address
 * @type {Object} TrackingEvent
 * @property {string} path A page from which the link was clicked.
 * @property {string|undefined} [subpath] A subpage from which the link was clicked.
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
			editHref={ editHref }
			editEventName="gla_edit_mc_store_address"
			loading={ ! loaded }
			warning={ warning }
			content={ content }
		></ContactInformationPreviewCard>
	);
}
