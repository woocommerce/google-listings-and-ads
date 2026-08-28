/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { createInterpolateElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TrackableLink from '~/components/trackable-link';
import AppButton from '~/components/app-button';
import AppModal from '~/components/app-modal';
import { recordGlaEvent } from '~/utils/tracks';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGTINMigrationStatus from '~/hooks/useGTINMigrationStatus';
import './gtin-migration-banner.scss';

const GTIN_MIGRATION_BANNER_CONTEXT = 'gtin_migration_banner';
const GTIN_MIGRATION_READY = 'ready';
const GTIN_MIGRATION_STARTED = 'started';

const LinkGTINMigrationJobStatusPage = ( props ) => (
	<TrackableLink
		eventName="gla_gtin_migration_banner_status_link_click"
		eventProps={ {
			context: GTIN_MIGRATION_BANNER_CONTEXT,
		} }
		href={
			'admin.php?page=wc-status&tab=action-scheduler&s=migrate_gtin&orderby=schedule&order=desc'
		}
		target="_blank"
		type="external"
		{ ...props }
	/>
);

const GtinMigrationBanner = () => {
	const { createNotice } = useDispatchCoreNotices();
	const [ showModal, setShowModal ] = useState( false );
	const [ status, loading, startMigration ] = useGTINMigrationStatus();

	if (
		status !== GTIN_MIGRATION_READY &&
		status !== GTIN_MIGRATION_STARTED
	) {
		return null;
	}

	const closeModal = () => {
		recordGlaEvent( 'gla_modal_closed', {
			context: GTIN_MIGRATION_BANNER_CONTEXT,
		} );
		setShowModal( false );
	};

	const openModal = () => {
		recordGlaEvent( 'gla_modal_open', {
			context: GTIN_MIGRATION_BANNER_CONTEXT,
		} );
		setShowModal( true );
	};

	const handleStartMigrationClick = async () => {
		recordGlaEvent( 'gla_gtin_migration_banner_migration_start', {
			context: GTIN_MIGRATION_BANNER_CONTEXT,
		} );
		try {
			await startMigration();
			recordGlaEvent( 'gla_gtin_migration_banner_migration_scheduled', {
				context: GTIN_MIGRATION_BANNER_CONTEXT,
			} );
			setShowModal( false );
			createNotice(
				'info',
				__(
					'GTIN Migration was successfully scheduled.',
					'google-listings-and-ads'
				)
			);
		} catch ( e ) {
			recordGlaEvent( 'gla_gtin_migration_banner_migration_failed', {
				context: GTIN_MIGRATION_BANNER_CONTEXT,
				error: e.message,
			} );
			createNotice(
				'error',
				__(
					'Unable to start GTIN Migration.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<>
			{ showModal && (
				<AppModal
					buttons={ [
						<AppButton key="1" onClick={ closeModal } isSecondary>
							{ __( 'Never mind', 'google-listings-and-ads' ) }
						</AppButton>,
						<AppButton
							disabled={ loading }
							key="2"
							onClick={ handleStartMigrationClick }
							isPrimary
						>
							{ __(
								'Start migration',
								'google-listings-and-ads'
							) }
						</AppButton>,
					] }
					className="gla-gtin-migration-banner-modal"
					onRequestClose={ closeModal }
					title={ __(
						'Before you start the migration…',
						'google-listings-and-ads'
					) }
				>
					<p>
						{ createInterpolateElement(
							__(
								"This migration will copy all GTIN numbers set in the Google for WooCommerce Product tab into the new GTIN field under the Product Inventory tab. If you have already set GTIN numbers in some of your products' Inventory tab, they will not be overridden. The GTIN numbers in the Google for WooCommerce tab will not be removed. The migration will run in the background and is not reversible. You can check the migration process on the <link>WooCommerce Scheduled Actions page</link>.",
								'google-listings-and-ads'
							),
							{
								link: <LinkGTINMigrationJobStatusPage />,
							}
						) }
					</p>
				</AppModal>
			) }
			{ status === GTIN_MIGRATION_READY && (
				<Notice isDismissible={ false }>
					{ createInterpolateElement(
						__(
							"The GTIN field managed by WooCommerce in the Product's inventory section, will now be used by Google for WooCommerce. It will continue to support the previous field and any mapping rules you have setup for the GTIN field. If you would like to migrate the data <link>click here</link>.",
							'google-listings-and-ads'
						),
						{
							link: (
								<TrackableLink
									className="gla-gtin-migration__link"
									eventName="gla_gtin_migration_banner_click"
									eventProps={ {
										context: GTIN_MIGRATION_BANNER_CONTEXT,
									} }
									onClick={ openModal }
								/>
							),
						}
					) }
				</Notice>
			) }
			{ status === GTIN_MIGRATION_STARTED && (
				<Notice isDismissible={ false }>
					{ createInterpolateElement(
						__(
							'Your GTIN Migration is now running in the background. You can check the migration process on the <link>WooCommerce Scheduled Actions page</link>',
							'google-listings-and-ads'
						),
						{
							link: <LinkGTINMigrationJobStatusPage />,
						}
					) }
				</Notice>
			) }
		</>
	);
};
export default GtinMigrationBanner;
