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
import './store-address-card.scss';

/**
 * "Edit MC store address" Tracking event
 *
 * @event gla_edit_mc_store_address
 * @type {Object} TrackingEvent
 * @property {string} path A page from which the link was clicked.
 * @property {string|undefined} [subpath] A subpage from which the link was clicked.
 */

/**
 * Renders a component with a given store address.
 *
 * @fires gla_edit_mc_store_address Whenever "Edit in Settings" is clicked.
 *
 * @param {Object} props React props
 * @param {boolean} props.isPreview Is that a short preview or more formatted view?
 * @return {JSX.Element} Filled AccountCard component.
 */
export default function StoreAddressCard( { isPreview } ) {
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
			eventName="gla_edit_mc_store_address"
			eventProps={ { path: getPath(), subpath } }
		/>
	);

	let addressContent;
	let description = '';

	if ( ! isPreview ) {
		description = __(
			'Please confirm your store address for verification.',
			'google-listings-and-ads'
		);
	}

	if ( loaded ) {
		const { address, address2, city, state, country, postcode } = data;
		const stateAndCountry = state ? `${ state } - ${ country }` : country;

		if ( isPreview ) {
			description = [ address, address2, city, stateAndCountry, postcode ]
				.filter( Boolean )
				.join( ', ' );
		} else {
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
		}
	} else {
		addressContent = <Spinner />;
	}

	return (
		<AccountCard
			appearance={ APPEARANCE.ADDRESS }
			description={ description }
			hideIcon={ isPreview }
			indicator={ editButton }
		>
			{ ! isPreview && (
				<>
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
				</>
			) }
		</AccountCard>
	);
}
