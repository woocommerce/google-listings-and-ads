/**
 * Internal dependencies
 */
import getConversionCampaignStatusNotice from './getConversionCampaignStatusNotice';

describe( 'getConversionCampaignStatusNotice', () => {
	const timestamp = 1651422465 * 1000; // May 01 2022

	it( 'Campaigns have not been converted', () => {
		const adsCampaignConvertStatus = {
			status: 'unconverted',
		};

		expect(
			getConversionCampaignStatusNotice( adsCampaignConvertStatus )
		).toBe( 'BEFORE_CONVERSION' );
	} );
	it( 'Campaigns have been converted and it has not reached the maximum time to display the notice (1 month)', () => {
		const adsCampaignConvertStatus = {
			status: 'converted',
			updated: 1651336065, //Apr 30 2022
		};

		expect(
			getConversionCampaignStatusNotice(
				adsCampaignConvertStatus,
				timestamp
			)
		).toBe( 'AFTER_CONVERSION' );
	} );
	it( 'Campaigns have been converted and it has reached the maximum time to display the notice (1 month)', () => {
		const adsCampaignConvertStatus = {
			status: 'converted',
			updated: 1648657665, //Mar 30 2022
		};

		expect(
			getConversionCampaignStatusNotice(
				adsCampaignConvertStatus,
				timestamp
			)
		).toBeNull();
	} );
	it( 'WP option: gla_campaign_convert_status is null (so the conversion has not started yet)', () => {
		const adsCampaignConvertStatus = null;
		expect(
			getConversionCampaignStatusNotice( adsCampaignConvertStatus )
		).toBe( 'BEFORE_CONVERSION' );
	} );
} );
