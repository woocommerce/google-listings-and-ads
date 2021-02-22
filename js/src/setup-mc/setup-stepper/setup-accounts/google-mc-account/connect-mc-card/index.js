/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MerchantCenterSelectControl from '.~/components/merchant-center-select-control';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '../../content-button-layout';

const ConnectMCCard = () => {
	const [ value, setValue ] = useState();
	const { linkMCAccount } = useAppDispatch();

	const handleSelectChange = ( optionValue ) => {
		setValue( optionValue );
	};

	const handleConnectClick = () => {
		if ( ! value ) {
			return;
		}

		linkMCAccount( value );
	};

	return (
		<Section.Card>
			<Section.Card.Body>
				<Subsection.Title>
					{ __(
						'You have an existing Merchant Center account in WooCommerce',
						'google-listings-and-ads'
					) }
				</Subsection.Title>
				<ContentButtonLayout>
					<MerchantCenterSelectControl
						value={ value }
						onChange={ handleSelectChange }
					/>
					<Button isSecondary onClick={ handleConnectClick }>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</Button>
				</ContentButtonLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ConnectMCCard;
