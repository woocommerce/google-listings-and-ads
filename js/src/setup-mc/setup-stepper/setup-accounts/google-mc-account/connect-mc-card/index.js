/**
 * External dependencies
 */
import { Button, SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../../wcdl/section';
import Subsection from '../../../../../wcdl/subsection';
import ContentButtonLayout from '../../content-button-layout';

const CreateMCCard = () => {
	const [ value, setValue ] = useState();

	// TODO: list of merchant center accounts that can be connected.
	// This should come from backend API.
	const options = [
		{
			value: 123,
			label: 'Test MC account',
		},
	];

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
				<SelectControl
					value={ value }
					options={ options }
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
