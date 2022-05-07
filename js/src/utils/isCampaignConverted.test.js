/**
 * Internal dependencies
 */
import isCampaignConverted from './isCampaignConverted';
import { CAMPAIGN_TYPE } from '.~/constants';

describe( 'isCampaignConverted', () => {
	it( 'Shopping Campaign but not converted yet', () => {
		const adsCampaignConvertStatus = {
			status: 'unconverted',
		};

		expect(
			isCampaignConverted(
				adsCampaignConvertStatus,
				CAMPAIGN_TYPE.SHOPPING
			)
		).toBe( false );
	} );
	it( 'Shopping Campaign and it is converted', () => {
		const adsCampaignConvertStatus = {
			status: 'converted',
		};

		expect(
			isCampaignConverted(
				adsCampaignConvertStatus,
				CAMPAIGN_TYPE.SHOPPING
			)
		).toBe( true );
	} );

	it( 'PMax Campaign should not be converted', () => {
		const adsCampaignConvertStatus = {
			status: 'converted',
		};

		expect(
			isCampaignConverted(
				adsCampaignConvertStatus,
				CAMPAIGN_TYPE.PERFORMANCE_MAX
			)
		).toBe( false );
	} );

	it( 'adsCampaignConvertStatus is null, migration has not started', () => {
		const adsCampaignConvertStatus = null;
		expect( isCampaignConverted( adsCampaignConvertStatus ) ).toBe( false );
	} );
} );
