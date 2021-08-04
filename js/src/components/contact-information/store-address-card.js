/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CardDivider } from '@wordpress/components';
import { Spinner } from '@woocommerce/components';
import { external as externalIcon } from '@wordpress/icons';
import GridiconRefresh from 'gridicons/dist/refresh';

/**
 * Internal dependencies
 */
import useStoreAddress from '.~/hooks/useStoreAddress';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import AccountCard, { APPEARANCE } from '.~/components/account-card';
import AppButton from '.~/components/app-button';
import './store-address-card.scss';

export default function StoreAddressCard( { isPreview } ) {
	const { loaded, data, refetch } = useStoreAddress();
	const editButton = (
		<AppButton
			isSecondary
			icon={ externalIcon }
			iconSize={ 16 }
			iconPosition="right"
			target="_blank"
			href="admin.php?page=wc-settings"
			text={ __( 'Edit in Settings', 'google-listings-and-ads' ) }
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
