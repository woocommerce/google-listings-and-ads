/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, createInterpolateElement } from '@wordpress/element';
import {
	Flex,
	FlexBlock,
	Notice,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import { useAppDispatch } from '~/data';
import './modal.scss';

/**
 * @typedef {Object} Campaign
 * @property {number} id The unique identifier for the campaign.
 * @property {string} name The name of the campaign.
 */

/**
 * Modal component for EU Political Declaration. Displays a list of campaigns missing the declaration and allows users to confirm which campaigns contain political ads.
 *
 * @param {Object} props The component props.
 * @param {Campaign[]} props.campaigns An array of campaign objects that are missing the EU political declaration.
 * @param {Function} props.onRequestClose A callback function to be called when the modal is requested to be closed.
 *
 * @return {JSX.Element} The rendered Modal component.
 */
const Modal = ( { campaigns, onRequestClose } ) => {
	const [ declarations, setDeclarations ] = useState( () =>
		Object.fromEntries( campaigns.map( ( { id } ) => [ id, false ] ) )
	);
	const [ loading, setLoading ] = useState( false );
	const { invalidateResolution, setEuPoliticalCampaigns } = useAppDispatch();

	const handleToggleChange = ( id, value ) => {
		setDeclarations( { ...declarations, [ id ]: value } );
	};

	const handleConfirm = async () => {
		const payload = campaigns.map( ( { id } ) => ( {
			id,
			value: declarations[ id ],
		} ) );

		setLoading( true );

		try {
			await setEuPoliticalCampaigns( payload );
			invalidateResolution( 'getAdsCampaigns', [] );
			onRequestClose();
		} finally {
			setLoading( false );
		}
	};

	return (
		<AppModal
			title={ __(
				'Action required: EU political ads declaration',
				'google-listings-and-ads'
			) }
			buttons={ [
				<AppButton
					key="confirm-declaration"
					variant="primary"
					onClick={ handleConfirm }
					loading={ loading }
				>
					{ __( 'Confirm declaration', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			onRequestClose={ onRequestClose }
			className="gla-eu-political-declaration-modal"
		>
			<Notice status="warning" isDismissible={ false }>
				{ __(
					"After April 1, 2026, you won't be able to create or edit campaigns without completing this declaration.",
					'google-listings-and-ads'
				) }
			</Notice>

			<p>
				{ createInterpolateElement(
					__(
						'Your Google Ads campaigns are missing the required <link>EU political ads declaration</link>. Check any campaigns below that contain political ads.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								href="https://support.google.com/adspolicy/answer/6014595"
								linkId="eu-political-content"
								context="eu_political_declaration_modal"
							/>
						),
					}
				) }
			</p>

			<ul>
				{ campaigns.map( ( { id, name } ) => (
					<li key={ id }>
						<Flex>
							<FlexBlock>{ name }</FlexBlock>
							<FlexBlock>
								<ToggleGroupControl
									label={ __(
										'Ad type',
										'google-listings-and-ads'
									) }
									value={ declarations[ id ] }
									onChange={ ( value ) =>
										handleToggleChange( id, value )
									}
									isBlock
									hideLabelFromVision
									__nextHasNoMarginBottom
								>
									<ToggleGroupControlOption
										value={ true }
										label={ __(
											'Political',
											'google-listings-and-ads'
										) }
									/>
									<ToggleGroupControlOption
										value={ false }
										label={ __(
											'Non-political',
											'google-listings-and-ads'
										) }
									/>
								</ToggleGroupControl>
							</FlexBlock>
						</Flex>

						{ declarations[ id ] && (
							<Notice
								status="error"
								isDismissible={ false }
								className="gla-eu-political-declaration-modal__notice--error"
							>
								{ __(
									'Your ads will not run in the EU',
									'google-listings-and-ads'
								) }
							</Notice>
						) }
					</li>
				) ) }
			</ul>
		</AppModal>
	);
};

export default Modal;
