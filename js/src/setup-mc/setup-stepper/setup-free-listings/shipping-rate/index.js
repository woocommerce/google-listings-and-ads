/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import './index.scss';
import AppRadioContentControl from '../../../../components/app-radio-content-control';

const ShippingRate = () => {
	// TODO:
	const [ selected, setSelected ] = useState( '' );

	const handleOptionSelected = ( option ) => {
		setSelected( option );
	};

	return (
		<Section
			title={ __( 'Shipping rate', 'google-listings-and-ads' ) }
			description={
				<div>
					<p>
						{ __(
							'Your estimated shipping rate will be shown to potential customers on Google.',
							'google-listings-and-ads'
						) }
					</p>
					<p>
						{ /* TODO: Link to read more on shipping rate. */ }
						<Link
							type="external"
							href="https://docs.woocommerce.com/"
							target="_blank"
						>
							{ __( 'Read more', 'google-listings-and-ads' ) }
						</Link>
					</p>
				</div>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					<AppRadioContentControl
						label={ __(
							'I have a fairly simple shipping setup and I can estimate flat shipping rates.',
							'google-listings-and-ads'
						) }
						value="simple"
						selected={ selected }
						onChange={ handleOptionSelected }
					>
						TODO
					</AppRadioContentControl>
					<AppRadioContentControl
						label={ __(
							'I have a more complex shipping setup and I cannot estimate flat shipping rates.',
							'google-listings-and-ads'
						) }
						value="complex"
						selected={ selected }
						onChange={ handleOptionSelected }
					>
						TODO
					</AppRadioContentControl>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default ShippingRate;
