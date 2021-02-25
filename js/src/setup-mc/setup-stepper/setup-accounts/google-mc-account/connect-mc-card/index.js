/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MerchantCenterSelectControl from '.~/components/merchant-center-select-control';
import AppButton from '.~/components/app-button';
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import useApiFetch from '.~/hooks/useApiFetch';
import { useAppDispatch } from '.~/data';
import ContentButtonLayout from '../../content-button-layout';
import SwitchUrlCard from '../switch-url-card';

const ConnectMCCard = () => {
	const [ value, setValue ] = useState();
	const [
		apiFetch,
		{ loading, error, response, options, reset },
	] = useApiFetch();
	const { receiveMCAccount } = useAppDispatch();

	const handleConnectClick = async () => {
		if ( ! value ) {
			return;
		}

		const { data } = await apiFetch( {
			path: `/wc/gla/mc/accounts`,
			method: 'POST',
			data: { id: value },
		} );

		receiveMCAccount( data );
	};

	if ( response && response.status === 409 ) {
		return (
			<SwitchUrlCard
				id={ options.data.id }
				message={ error.message }
				onSelectAnotherAccount={ reset }
			/>
		);
	}

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
						onChange={ setValue }
					/>
					<AppButton
						isSecondary
						loading={ loading }
						disabled={ ! value }
						onClick={ handleConnectClick }
					>
						{ __( 'Connect', 'google-listings-and-ads' ) }
					</AppButton>
				</ContentButtonLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default ConnectMCCard;
