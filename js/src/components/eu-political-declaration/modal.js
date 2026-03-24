/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, createInterpolateElement } from '@wordpress/element';
import { CheckboxControl, Notice } from '@wordpress/components';

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
	const [ individualMode, setIndividualMode ] = useState( false );
	const [ loading, setLoading ] = useState( false );
	const { invalidateResolution, setEuPoliticalCampaigns } = useAppDispatch();
	const [ declarations, setDeclarations ] = useState( () =>
		Object.fromEntries( campaigns.map( ( { id } ) => [ id, false ] ) )
	);

	const submitDeclaration = async ( payload ) => {
		setLoading( true );

		try {
			await setEuPoliticalCampaigns( payload );
			invalidateResolution( 'getAdsCampaigns', [] );
			onRequestClose();
		} finally {
			setLoading( false );
		}
	};

	const handleCheckboxChange = ( id, value ) => {
		setDeclarations( { ...declarations, [ id ]: value } );
	};

	const handleSelectAll = () => {
		setDeclarations(
			Object.fromEntries( campaigns.map( ( { id } ) => [ id, true ] ) )
		);
	};

	const handleClickSomePolitical = () => {
		setIndividualMode( true );
	};

	const handleClickAllNonPolitical = () =>
		submitDeclaration(
			campaigns.map( ( { id } ) => ( { id, value: false } ) )
		);

	const handleConfirmDeclaration = () =>
		submitDeclaration(
			campaigns.map( ( { id } ) => ( { id, value: declarations[ id ] } ) )
		);

	const buttons = individualMode
		? [
				<AppButton
					key="confirm-declaration"
					variant="primary"
					onClick={ handleConfirmDeclaration }
					loading={ loading }
				>
					{ __( 'Confirm declaration', 'google-listings-and-ads' ) }
				</AppButton>,
		  ]
		: [
				<AppButton
					key="declare-some-political"
					variant="tertiary"
					onClick={ handleClickSomePolitical }
				>
					{ __( 'Some are political', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="declare-all-non-political"
					variant="primary"
					onClick={ handleClickAllNonPolitical }
					loading={ loading }
				>
					{ __(
						'Declare all as non-political',
						'google-listings-and-ads'
					) }
				</AppButton>,
		  ];

	return (
		<AppModal
			title={ __(
				'Action required: EU political ads declaration',
				'google-listings-and-ads'
			) }
			buttons={ buttons }
			onRequestClose={ onRequestClose }
			className="gla-eu-political-declaration-modal"
		>
			<p>
				{ createInterpolateElement(
					__(
						'Your Google Ads campaigns are missing the required EU political ads declaration. <link>Learn about political ads</link>',
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

			{ ! individualMode && (
				<Notice
					status="warning"
					isDismissible={ false }
					className="gla-eu-political-declaration-modal__notice--warning"
				>
					{ __(
						"After April 1, 2026, you won't be able to create or edit campaigns without completing this declaration.",
						'google-listings-and-ads'
					) }
				</Notice>
			) }

			{ individualMode && (
				<p className="gla-eu-political-declaration-modal__subtitle">
					{ __(
						'Select campaigns that contain political ads:',
						'google-listings-and-ads'
					) }
				</p>
			) }

			<ul>
				{ campaigns.map( ( { id, name } ) => (
					<li key={ id }>
						{ ! individualMode && <span>{ name }</span> }

						{ individualMode && (
							<CheckboxControl
								label={ name }
								checked={ declarations[ id ] }
								onChange={ ( value ) =>
									handleCheckboxChange( id, value )
								}
							/>
						) }
					</li>
				) ) }
			</ul>

			{ individualMode && (
				<>
					<AppButton variant="link" onClick={ handleSelectAll }>
						{ __( 'Select all', 'google-listings-and-ads' ) }
					</AppButton>

					{ Object.values( declarations ).some(
						( value ) => value
					) && (
						<Notice
							status="warning"
							isDismissible={ false }
							className="gla-eu-political-declaration-modal__notice--warning"
						>
							{ __(
								'Campaigns marked as political will not run in EU countries.',
								'google-listings-and-ads'
							) }
						</Notice>
					) }
				</>
			) }
		</AppModal>
	);
};

export default Modal;
