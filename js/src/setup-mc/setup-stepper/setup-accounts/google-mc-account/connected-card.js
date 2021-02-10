/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import TitleButtonLayout from '../title-button-layout';

const ConnectedCard = ( props ) => {
	const { googleMCAccount } = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<TitleButtonLayout
					title={ createInterpolateElement(
						__(
							'Account <accountnumber />',
							'google-listings-and-ads'
						),
						{
							accountnumber: <span>{ googleMCAccount.id }</span>,
						}
					) }
				/>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ConnectedCard;
