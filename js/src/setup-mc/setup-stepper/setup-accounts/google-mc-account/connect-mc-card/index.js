/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../../wcdl/section';
import Subsection from '../../../../../wcdl/subsection';
import MerchantCenterSelectControl from '../../../../../components/merchant-center-select-control';
import ContentButtonLayout from '../../content-button-layout';

const CreateMCCard = () => {
	const [ value, setValue ] = useState();

	const handleSelectChange = ( optionValue ) => {
		setValue( optionValue );
	};

	const handleConnectClick = () => {};

	return (
		<Section.Card>
			<Subsection.Title>
				{ __(
					'Connect your Merchant Center',
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
		</Section.Card>
	);
};

export default CreateMCCard;
