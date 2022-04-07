<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;

/**
 * Class GoogleHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 *
 * @since 1.12.0
 */
class GoogleHelper implements Service {

	protected const SUPPORTED_COUNTRIES = [
		// Algeria
		'DZ' => [
			'code'     => 'DZ',
			'currency' => 'DZD',
			'id'       => 2012,
		],
		// Angola
		'AO' => [
			'code'     => 'AO',
			'currency' => 'AOA',
			'id'       => 2024,
		],
		// Argentina
		'AR' => [
			'code'     => 'AR',
			'currency' => 'ARS',
			'id'       => 2032,
		],
		// Australia
		'AU' => [
			'code'     => 'AU',
			'currency' => 'AUD',
			'id'       => 2036,
		],
		// Austria
		'AT' => [
			'code'     => 'AT',
			'currency' => 'EUR',
			'id'       => 2040,
		],
		// Bahrain
		'BH' => [
			'code'     => 'BH',
			'currency' => 'BHD',
			'id'       => 2048,
		],
		// Bangladesh
		'BD' => [
			'code'     => 'BD',
			'currency' => 'BDT',
			'id'       => 2050,
		],
		// Belarus
		'BY' => [
			'code'     => 'BY',
			'currency' => 'BYN',
			'id'       => 2112,
		],
		// Belgium
		'BE' => [
			'code'     => 'BE',
			'currency' => 'EUR',
			'id'       => 2056,
		],
		// Brazil
		'BR' => [
			'code'     => 'BR',
			'currency' => 'BRL',
			'id'       => 2076,
		],
		// Cambodia
		'KH' => [
			'code'     => 'KH',
			'currency' => 'KHR',
			'id'       => 2116,
		],
		// Cameroon
		'CM' => [
			'code'     => 'CM',
			'currency' => 'XAF',
			'id'       => 2120,
		],
		// Canada
		'CA' => [
			'code'     => 'CA',
			'currency' => 'CAD',
			'id'       => 2124,
		],
		// Chile
		'CL' => [
			'code'     => 'CL',
			'currency' => 'CLP',
			'id'       => 2152,
		],
		// Colombia
		'CO' => [
			'code'     => 'CO',
			'currency' => 'COP',
			'id'       => 2170,
		],
		// Costa Rica
		'CR' => [
			'code'     => 'CR',
			'currency' => 'CRC',
			'id'       => 2188,
		],
		// Cote d'Ivoire
		'CI' => [
			'code'     => 'CI',
			'currency' => 'XOF',
			'id'       => 2384,
		],
		// Czechia
		'CZ' => [
			'code'     => 'CZ',
			'currency' => 'CZK',
			'id'       => 2203,
		],
		// Denmark
		'DK' => [
			'code'     => 'DK',
			'currency' => 'DKK',
			'id'       => 2208,
		],
		// Dominican Republic
		'DO' => [
			'code'     => 'DO',
			'currency' => 'DOP',
			'id'       => 2214,
		],
		// Ecuador
		'EC' => [
			'code'     => 'EC',
			'currency' => 'USD',
			'id'       => 2218,
		],
		// Egypt
		'EG' => [
			'code'     => 'EG',
			'currency' => 'EGP',
			'id'       => 2818,
		],
		// El Salvador
		'SV' => [
			'code'     => 'SV',
			'currency' => 'USD',
			'id'       => 2222,
		],
		// Ethiopia
		'ET' => [
			'code'     => 'ET',
			'currency' => 'ETB',
			'id'       => 2231,
		],
		// Finland
		'FI' => [
			'code'     => 'FI',
			'currency' => 'EUR',
			'id'       => 2246,
		],
		// France
		'FR' => [
			'code'     => 'FR',
			'currency' => 'EUR',
			'id'       => 2250,
		],
		// Georgia
		'GE' => [
			'code'     => 'GE',
			'currency' => 'GEL',
			'id'       => 2268,
		],
		// Germany
		'DE' => [
			'code'     => 'DE',
			'currency' => 'EUR',
			'id'       => 2276,
		],
		// Ghana
		'GH' => [
			'code'     => 'GH',
			'currency' => 'GHS',
			'id'       => 2288,
		],
		// Greece
		'GR' => [
			'code'     => 'GR',
			'currency' => 'EUR',
			'id'       => 2300,
		],
		// Guatemala
		'GT' => [
			'code'     => 'GT',
			'currency' => 'GTQ',
			'id'       => 2320,
		],
		// Hong Kong
		'HK' => [
			'code'     => 'HK',
			'currency' => 'HKD',
			'id'       => 2344,
		],
		// Hungary
		'HU' => [
			'code'     => 'HU',
			'currency' => 'HUF',
			'id'       => 2348,
		],
		// India
		'IN' => [
			'code'     => 'IN',
			'currency' => 'INR',
			'id'       => 2356,
		],
		// Indonesia
		'ID' => [
			'code'     => 'ID',
			'currency' => 'IDR',
			'id'       => 2360,
		],
		// Ireland
		'IE' => [
			'code'     => 'IE',
			'currency' => 'EUR',
			'id'       => 2372,
		],
		// Israel
		'IL' => [
			'code'     => 'IL',
			'currency' => 'ILS',
			'id'       => 2376,
		],
		// Italy
		'IT' => [
			'code'     => 'IT',
			'currency' => 'EUR',
			'id'       => 2380,
		],
		// Japan
		'JP' => [
			'code'     => 'JP',
			'currency' => 'JPY',
			'id'       => 2392,
		],
		// Jordan
		'JO' => [
			'code'     => 'JO',
			'currency' => 'JOD',
			'id'       => 2400,
		],
		// Kazakhstan
		'KZ' => [
			'code'     => 'KZ',
			'currency' => 'KZT',
			'id'       => 2398,
		],
		// Kenya
		'KE' => [
			'code'     => 'KE',
			'currency' => 'KES',
			'id'       => 2404,
		],
		// Kuwait
		'KW' => [
			'code'     => 'KW',
			'currency' => 'KWD',
			'id'       => 2414,
		],
		// Lebanon
		'LB' => [
			'code'     => 'LB',
			'currency' => 'LBP',
			'id'       => 2422,
		],
		// Madagascar
		'MG' => [
			'code'     => 'MG',
			'currency' => 'MGA',
			'id'       => 2450,
		],
		// Malaysia
		'MY' => [
			'code'     => 'MY',
			'currency' => 'MYR',
			'id'       => 2458,
		],
		// Mauritius
		'MU' => [
			'code'     => 'MU',
			'currency' => 'MUR',
			'id'       => 2480,
		],
		// Mexico
		'MX' => [
			'code'     => 'MX',
			'currency' => 'MXN',
			'id'       => 2484,
		],
		// Morocco
		'MA' => [
			'code'     => 'MA',
			'currency' => 'MAD',
			'id'       => 2504,
		],
		// Mozambique
		'MZ' => [
			'code'     => 'MZ',
			'currency' => 'MZN',
			'id'       => 2508,
		],
		// Myanmar 'Burma'
		'MM' => [
			'code'     => 'MM',
			'currency' => 'MMK',
			'id'       => 2104,
		],
		// Nepal
		'NP' => [
			'code'     => 'NP',
			'currency' => 'NPR',
			'id'       => 2524,
		],
		// Netherlands
		'NL' => [
			'code'     => 'NL',
			'currency' => 'EUR',
			'id'       => 2528,
		],
		// New Zealand
		'NZ' => [
			'code'     => 'NZ',
			'currency' => 'NZD',
			'id'       => 2554,
		],
		// Nicaragua
		'NI' => [
			'code'     => 'NI',
			'currency' => 'NIO',
			'id'       => 2558,
		],
		// Nigeria
		'NG' => [
			'code'     => 'NG',
			'currency' => 'NGN',
			'id'       => 2566,
		],
		// Norway
		'NO' => [
			'code'     => 'NO',
			'currency' => 'NOK',
			'id'       => 2578,
		],
		// Oman
		'OM' => [
			'code'     => 'OM',
			'currency' => 'OMR',
			'id'       => 2512,
		],
		// Pakistan
		'PK' => [
			'code'     => 'PK',
			'currency' => 'PKR',
			'id'       => 2586,
		],
		// Panama
		'PA' => [
			'code'     => 'PA',
			'currency' => 'PAB',
			'id'       => 2591,
		],
		// Paraguay
		'PY' => [
			'code'     => 'PY',
			'currency' => 'PYG',
			'id'       => 2600,
		],
		// Peru
		'PE' => [
			'code'     => 'PE',
			'currency' => 'PEN',
			'id'       => 2604,
		],
		// Philippines
		'PH' => [
			'code'     => 'PH',
			'currency' => 'PHP',
			'id'       => 2608,
		],
		// Poland
		'PL' => [
			'code'     => 'PL',
			'currency' => 'PLN',
			'id'       => 2616,
		],
		// Portugal
		'PT' => [
			'code'     => 'PT',
			'currency' => 'EUR',
			'id'       => 2620,
		],
		// Puerto Rico
		'PR' => [
			'code'     => 'PR',
			'currency' => 'USD',
			'id'       => 2630,
		],
		// Romania
		'RO' => [
			'code'     => 'RO',
			'currency' => 'RON',
			'id'       => 2642,
		],
		// Russia
		'RU' => [
			'code'     => 'RU',
			'currency' => 'RUB',
			'id'       => 2643,
		],
		// Saudi Arabia
		'SA' => [
			'code'     => 'SA',
			'currency' => 'SAR',
			'id'       => 2682,
		],
		// Senegal
		'SN' => [
			'code'     => 'SN',
			'currency' => 'XOF',
			'id'       => 2686,
		],
		// Singapore
		'SG' => [
			'code'     => 'SG',
			'currency' => 'SGD',
			'id'       => 2702,
		],
		// Slovakia
		'SK' => [
			'code'     => 'SK',
			'currency' => 'EUR',
			'id'       => 2703,
		],
		// South Africa
		'ZA' => [
			'code'     => 'ZA',
			'currency' => 'ZAR',
			'id'       => 2710,
		],
		// Spain
		'ES' => [
			'code'     => 'ES',
			'currency' => 'EUR',
			'id'       => 2724,
		],
		// Sri Lanka
		'LK' => [
			'code'     => 'LK',
			'currency' => 'LKR',
			'id'       => 2144,
		],
		// Sweden
		'SE' => [
			'code'     => 'SE',
			'currency' => 'SEK',
			'id'       => 2752,
		],
		// Switzerland
		'CH' => [
			'code'     => 'CH',
			'currency' => 'CHF',
			'id'       => 2756,
		],
		// Taiwan
		'TW' => [
			'code'     => 'TW',
			'currency' => 'TWD',
			'id'       => 2158,
		],
		// Tanzania
		'TZ' => [
			'code'     => 'TZ',
			'currency' => 'TZS',
			'id'       => 2834,
		],
		// Thailand
		'TH' => [
			'code'     => 'TH',
			'currency' => 'THB',
			'id'       => 2764,
		],
		// Tunisia
		'TN' => [
			'code'     => 'TN',
			'currency' => 'TND',
			'id'       => 2788,
		],
		// Turkey
		'TR' => [
			'code'     => 'TR',
			'currency' => 'TRY',
			'id'       => 2792,
		],
		// United Arab Emirates
		'AE' => [
			'code'     => 'AE',
			'currency' => 'AED',
			'id'       => 2784,
		],
		// Uganda
		'UG' => [
			'code'     => 'UG',
			'currency' => 'UGX',
			'id'       => 2800,
		],
		// Ukraine
		'UA' => [
			'code'     => 'UA',
			'currency' => 'UAH',
			'id'       => 2804,
		],
		// United Kingdom
		'GB' => [
			'code'     => 'GB',
			'currency' => 'GBP',
			'id'       => 2826,
		],
		// United States
		'US' => [
			'code'     => 'US',
			'currency' => 'USD',
			'id'       => 2840,
		],
		// Uruguay
		'UY' => [
			'code'     => 'UY',
			'currency' => 'UYU',
			'id'       => 2858,
		],
		// Uzbekistan
		'UZ' => [
			'code'     => 'UZ',
			'currency' => 'UZS',
			'id'       => 2860,
		],
		// Venezuela
		'VE' => [
			'code'     => 'VE',
			'currency' => 'VEF',
			'id'       => 2862,
		],
		// Vietnam
		'VN' => [
			'code'     => 'VN',
			'currency' => 'VND',
			'id'       => 2704,
		],
		// Zambia
		'ZM' => [
			'code'     => 'ZM',
			'currency' => 'ZMW',
			'id'       => 2894,
		],
		// Zimbabwe
		'ZW' => [
			'code'     => 'ZW',
			'currency' => 'USD',
			'id'       => 2716,
		],
	];

	protected const COUNTRY_SUBDIVISIONS = [
		// Australia
		'AU' => [
			'ACT' => [
				'id'   => 20034,
				'code' => 'ACT',
				'name' => 'Australian Capital Territory',
			],
			'NSW' => [
				'id'   => 20035,
				'code' => 'NSW',
				'name' => 'New South Wales',
			],
			'NT'  => [
				'id'   => 20036,
				'code' => 'NT',
				'name' => 'Northern Territory',
			],
			'QLD' => [
				'id'   => 20037,
				'code' => 'QLD',
				'name' => 'Queensland',
			],
			'SA'  => [
				'id'   => 20038,
				'code' => 'SA',
				'name' => 'South Australia',
			],
			'TAS' => [
				'id'   => 20039,
				'code' => 'TAS',
				'name' => 'Tasmania',
			],
			'VIC' => [
				'id'   => 20040,
				'code' => 'VIC',
				'name' => 'Victoria',
			],
			'WA'  => [
				'id'   => 20041,
				'code' => 'WA',
				'name' => 'Western Australia',
			],
		],
		// Japan
		'JP' => [
			'JP01' => [
				'id'   => 20624,
				'code' => 'JP01',
				'name' => 'Hokkaido',
			],
			'JP02' => [
				'id'   => 20625,
				'code' => 'JP02',
				'name' => 'Aomori',
			],
			'JP03' => [
				'id'   => 20626,
				'code' => 'JP03',
				'name' => 'Iwate',
			],
			'JP04' => [
				'id'   => 20627,
				'code' => 'JP04',
				'name' => 'Miyagi',
			],
			'JP05' => [
				'id'   => 20628,
				'code' => 'JP05',
				'name' => 'Akita',
			],
			'JP06' => [
				'id'   => 20629,
				'code' => 'JP06',
				'name' => 'Yamagata',
			],
			'JP07' => [
				'id'   => 20630,
				'code' => 'JP07',
				'name' => 'Fukushima',
			],
			'JP08' => [
				'id'   => 20631,
				'code' => 'JP08',
				'name' => 'Ibaraki',
			],
			'JP09' => [
				'id'   => 20632,
				'code' => 'JP09',
				'name' => 'Tochigi',
			],
			'JP10' => [
				'id'   => 20633,
				'code' => 'JP10',
				'name' => 'Gunma',
			],
			'JP11' => [
				'id'   => 20634,
				'code' => 'JP11',
				'name' => 'Saitama',
			],
			'JP12' => [
				'id'   => 20635,
				'code' => 'JP12',
				'name' => 'Chiba',
			],
			'JP13' => [
				'id'   => 20636,
				'code' => 'JP13',
				'name' => 'Tokyo',
			],
			'JP14' => [
				'id'   => 20637,
				'code' => 'JP14',
				'name' => 'Kanagawa',
			],
			'JP15' => [
				'id'   => 20638,
				'code' => 'JP15',
				'name' => 'Niigata',
			],
			'JP16' => [
				'id'   => 20639,
				'code' => 'JP16',
				'name' => 'Toyama',
			],
			'JP17' => [
				'id'   => 20640,
				'code' => 'JP17',
				'name' => 'Ishikawa',
			],
			'JP18' => [
				'id'   => 20641,
				'code' => 'JP18',
				'name' => 'Fukui',
			],
			'JP19' => [
				'id'   => 20642,
				'code' => 'JP19',
				'name' => 'Yamanashi',
			],
			'JP20' => [
				'id'   => 20643,
				'code' => 'JP20',
				'name' => 'Nagano',
			],
			'JP21' => [
				'id'   => 20644,
				'code' => 'JP21',
				'name' => 'Gifu',
			],
			'JP22' => [
				'id'   => 20645,
				'code' => 'JP22',
				'name' => 'Shizuoka',
			],
			'JP23' => [
				'id'   => 20646,
				'code' => 'JP23',
				'name' => 'Aichi',
			],
			'JP24' => [
				'id'   => 20647,
				'code' => 'JP24',
				'name' => 'Mie',
			],
			'JP25' => [
				'id'   => 20648,
				'code' => 'JP25',
				'name' => 'Shiga',
			],
			'JP26' => [
				'id'   => 20649,
				'code' => 'JP26',
				'name' => 'Kyoto',
			],
			'JP27' => [
				'id'   => 20650,
				'code' => 'JP27',
				'name' => 'Osaka',
			],
			'JP28' => [
				'id'   => 20651,
				'code' => 'JP28',
				'name' => 'Hyogo',
			],
			'JP29' => [
				'id'   => 20652,
				'code' => 'JP29',
				'name' => 'Nara',
			],
			'JP30' => [
				'id'   => 20653,
				'code' => 'JP30',
				'name' => 'Wakayama',
			],
			'JP31' => [
				'id'   => 20654,
				'code' => 'JP31',
				'name' => 'Tottori',
			],
			'JP32' => [
				'id'   => 20655,
				'code' => 'JP32',
				'name' => 'Shimane',
			],
			'JP33' => [
				'id'   => 20656,
				'code' => 'JP33',
				'name' => 'Okayama',
			],
			'JP34' => [
				'id'   => 20657,
				'code' => 'JP34',
				'name' => 'Hiroshima',
			],
			'JP35' => [
				'id'   => 20658,
				'code' => 'JP35',
				'name' => 'Yamaguchi',
			],
			'JP36' => [
				'id'   => 20659,
				'code' => 'JP36',
				'name' => 'Tokushima',
			],
			'JP37' => [
				'id'   => 20660,
				'code' => 'JP37',
				'name' => 'Kagawa',
			],
			'JP38' => [
				'id'   => 20661,
				'code' => 'JP38',
				'name' => 'Ehime',
			],
			'JP39' => [
				'id'   => 20662,
				'code' => 'JP39',
				'name' => 'Kochi',
			],
			'JP40' => [
				'id'   => 20663,
				'code' => 'JP40',
				'name' => 'Fukuoka',
			],
			'JP41' => [
				'id'   => 20664,
				'code' => 'JP41',
				'name' => 'Saga',
			],
			'JP42' => [
				'id'   => 20665,
				'code' => 'JP42',
				'name' => 'Nagasaki',
			],
			'JP43' => [
				'id'   => 20666,
				'code' => 'JP43',
				'name' => 'Kumamoto',
			],
			'JP44' => [
				'id'   => 20667,
				'code' => 'JP44',
				'name' => 'Oita',
			],
			'JP45' => [
				'id'   => 20668,
				'code' => 'JP45',
				'name' => 'Miyazaki',
			],
			'JP46' => [
				'id'   => 20669,
				'code' => 'JP46',
				'name' => 'Kagoshima',
			],
			'JP47' => [
				'id'   => 20670,
				'code' => 'JP47',
				'name' => 'Okinawa',
			],
		],
		// United States
		'US' => [
			'AK' => [
				'id'   => 21132,
				'code' => 'AK',
				'name' => 'Alaska',
			],
			'AL' => [
				'id'   => 21133,
				'code' => 'AL',
				'name' => 'Alabama',
			],
			'AR' => [
				'id'   => 21135,
				'code' => 'AR',
				'name' => 'Arkansas',
			],
			'AZ' => [
				'id'   => 21136,
				'code' => 'AZ',
				'name' => 'Arizona',
			],
			'CA' => [
				'id'   => 21137,
				'code' => 'CA',
				'name' => 'California',
			],
			'CO' => [
				'id'   => 21138,
				'code' => 'CO',
				'name' => 'Colorado',
			],
			'CT' => [
				'id'   => 21139,
				'code' => 'CT',
				'name' => 'Connecticut',
			],
			'DC' => [
				'id'   => 21140,
				'code' => 'DC',
				'name' => 'District of Columbia',
			],
			'DE' => [
				'id'   => 21141,
				'code' => 'DE',
				'name' => 'Delaware',
			],
			'FL' => [
				'id'   => 21142,
				'code' => 'FL',
				'name' => 'Florida',
			],
			'GA' => [
				'id'   => 21143,
				'code' => 'GA',
				'name' => 'Georgia',
			],
			'HI' => [
				'id'   => 21144,
				'code' => 'HI',
				'name' => 'Hawaii',
			],
			'IA' => [
				'id'   => 21145,
				'code' => 'IA',
				'name' => 'Iowa',
			],
			'ID' => [
				'id'   => 21146,
				'code' => 'ID',
				'name' => 'Idaho',
			],
			'IL' => [
				'id'   => 21147,
				'code' => 'IL',
				'name' => 'Illinois',
			],
			'IN' => [
				'id'   => 21148,
				'code' => 'IN',
				'name' => 'Indiana',
			],
			'KS' => [
				'id'   => 21149,
				'code' => 'KS',
				'name' => 'Kansas',
			],
			'KY' => [
				'id'   => 21150,
				'code' => 'KY',
				'name' => 'Kentucky',
			],
			'LA' => [
				'id'   => 21151,
				'code' => 'LA',
				'name' => 'Louisiana',
			],
			'MA' => [
				'id'   => 21152,
				'code' => 'MA',
				'name' => 'Massachusetts',
			],
			'MD' => [
				'id'   => 21153,
				'code' => 'MD',
				'name' => 'Maryland',
			],
			'ME' => [
				'id'   => 21154,
				'code' => 'ME',
				'name' => 'Maine',
			],
			'MI' => [
				'id'   => 21155,
				'code' => 'MI',
				'name' => 'Michigan',
			],
			'MN' => [
				'id'   => 21156,
				'code' => 'MN',
				'name' => 'Minnesota',
			],
			'MO' => [
				'id'   => 21157,
				'code' => 'MO',
				'name' => 'Missouri',
			],
			'MS' => [
				'id'   => 21158,
				'code' => 'MS',
				'name' => 'Mississippi',
			],
			'MT' => [
				'id'   => 21159,
				'code' => 'MT',
				'name' => 'Montana',
			],
			'NC' => [
				'id'   => 21160,
				'code' => 'NC',
				'name' => 'North Carolina',
			],
			'ND' => [
				'id'   => 21161,
				'code' => 'ND',
				'name' => 'North Dakota',
			],
			'NE' => [
				'id'   => 21162,
				'code' => 'NE',
				'name' => 'Nebraska',
			],
			'NH' => [
				'id'   => 21163,
				'code' => 'NH',
				'name' => 'New Hampshire',
			],
			'NJ' => [
				'id'   => 21164,
				'code' => 'NJ',
				'name' => 'New Jersey',
			],
			'NM' => [
				'id'   => 21165,
				'code' => 'NM',
				'name' => 'New Mexico',
			],
			'NV' => [
				'id'   => 21166,
				'code' => 'NV',
				'name' => 'Nevada',
			],
			'NY' => [
				'id'   => 21167,
				'code' => 'NY',
				'name' => 'New York',
			],
			'OH' => [
				'id'   => 21168,
				'code' => 'OH',
				'name' => 'Ohio',
			],
			'OK' => [
				'id'   => 21169,
				'code' => 'OK',
				'name' => 'Oklahoma',
			],
			'OR' => [
				'id'   => 21170,
				'code' => 'OR',
				'name' => 'Oregon',
			],
			'PA' => [
				'id'   => 21171,
				'code' => 'PA',
				'name' => 'Pennsylvania',
			],
			'RI' => [
				'id'   => 21172,
				'code' => 'RI',
				'name' => 'Rhode Island',
			],
			'SC' => [
				'id'   => 21173,
				'code' => 'SC',
				'name' => 'South Carolina',
			],
			'SD' => [
				'id'   => 21174,
				'code' => 'SD',
				'name' => 'South Dakota',
			],
			'TN' => [
				'id'   => 21175,
				'code' => 'TN',
				'name' => 'Tennessee',
			],
			'TX' => [
				'id'   => 21176,
				'code' => 'TX',
				'name' => 'Texas',
			],
			'UT' => [
				'id'   => 21177,
				'code' => 'UT',
				'name' => 'Utah',
			],
			'VA' => [
				'id'   => 21178,
				'code' => 'VA',
				'name' => 'Virginia',
			],
			'VT' => [
				'id'   => 21179,
				'code' => 'VT',
				'name' => 'Vermont',
			],
			'WA' => [
				'id'   => 21180,
				'code' => 'WA',
				'name' => 'Washington',
			],
			'WI' => [
				'id'   => 21182,
				'code' => 'WI',
				'name' => 'Wisconsin',
			],
			'WV' => [
				'id'   => 21183,
				'code' => 'WV',
				'name' => 'West Virginia',
			],
			'WY' => [
				'id'   => 21184,
				'code' => 'WY',
				'name' => 'Wyoming',
			],
		],
	];

	/**
	 * @var WC
	 */
	protected $wc;

	/**
	 * @var array Map of location ids to country codes.
	 */
	private $country_id_code_map;

	/**
	 * @var array Map of location ids to subdivision codes.
	 */
	private $subdivision_id_code_map;

	/**
	 * GoogleHelper constructor.
	 *
	 * @param WC $wc
	 */
	public function __construct( WC $wc ) {
		$this->wc = $wc;
	}


	/**
	 * Get the data for countries supported by Google.
	 *
	 * @return array[]
	 */
	protected function get_mc_supported_countries_data(): array {
		$supported = self::SUPPORTED_COUNTRIES;

		// Currency conversion is unavailable in South Korea: https://support.google.com/merchants/answer/7055540
		if ( 'KRW' === $this->wc->get_woocommerce_currency() ) {
			// South Korea
			$supported['KR'] = [
				'code'     => 'KR',
				'currency' => 'KRW',
				'id'       => 2410,
			];
		}

		return $supported;
	}

	/**
	 * Get an array of Google Merchant Center supported countries and currencies.
	 *
	 * Note - Other currencies may be supported using currency conversion.
	 *
	 * WooCommerce Countries -> https://github.com/woocommerce/woocommerce/blob/master/i18n/countries.php
	 * Google Supported Countries -> https://support.google.com/merchants/answer/160637?hl=en
	 *
	 * @return array
	 */
	public function get_mc_supported_countries_currencies(): array {
		return array_column(
			$this->get_mc_supported_countries_data(),
			'currency',
			'code'
		);
	}

	/**
	 * Get an array of Google Merchant Center supported countries.
	 *
	 * WooCommerce Countries -> https://github.com/woocommerce/woocommerce/blob/master/i18n/countries.php
	 * Google Supported Countries -> https://support.google.com/merchants/answer/160637?hl=en
	 *
	 * @return string[]
	 */
	public function get_mc_supported_countries(): array {
		return array_keys( $this->get_mc_supported_countries_data() );
	}

	/**
	 * Get an array of Google Merchant Center supported countries and currencies for promotions.
	 *
	 * @return array
	 */
	protected function get_mc_promotion_supported_countries_currencies(): array {
		return [
			'US' => 'USD', // United States
		];
	}

	/**
	 * Get an array of Google Merchant Center supported countries for promotions.
	 *
	 * @return string[]
	 */
	public function get_mc_promotion_supported_countries(): array {
		return array_keys( $this->get_mc_promotion_supported_countries_currencies() );
	}

	/**
	 * Get an array of Google Merchant Center supported languages (ISO 639-1).
	 *
	 * WooCommerce Languages -> https://translate.wordpress.org/projects/wp-plugins/woocommerce/
	 * Google Supported Languages -> https://support.google.com/merchants/answer/160637?hl=en
	 * ISO 639-1 -> https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
	 *
	 * @return array
	 */
	public function get_mc_supported_languages(): array {
		// Repeated values removed:
		// 'pt', // Brazilian Portuguese
		// 'zh', // Simplified Chinese*

		return [
			'ar' => 'ar', // Arabic
			'cs' => 'cs', // Czech
			'da' => 'da', // Danish
			'nl' => 'nl', // Dutch
			'en' => 'en', // English
			'fi' => 'fi', // Finnish
			'fr' => 'fr', // French
			'de' => 'de', // German
			'he' => 'he', // Hebrew
			'hu' => 'hu', // Hungarian
			'id' => 'id', // Indonesian
			'it' => 'it', // Italian
			'ja' => 'ja', // Japanese
			'ko' => 'ko', // Korean
			'el' => 'el', // Modern Greek
			'nb' => 'nb', // Norwegian (Norsk Bokmål)
			'nn' => 'nn', // Norwegian (Norsk Nynorsk)
			'no' => 'no', // Norwegian
			'pl' => 'pl', // Polish
			'pt' => 'pt', // Portuguese
			'ro' => 'ro', // Romanian
			'ru' => 'ru', // Russian
			'sk' => 'sk', // Slovak
			'es' => 'es', // Spanish
			'sv' => 'sv', // Swedish
			'th' => 'th', // Thai
			'zh' => 'zh', // Traditional Chinese
			'tr' => 'tr', // Turkish
			'uk' => 'uk', // Ukrainian
			'vi' => 'vi', // Vietnamese
		];
	}

	/**
	 * Get whether the country is supported by the Merchant Center.
	 *
	 * @param string $country Country code.
	 *
	 * @return bool True if the country is in the list of MC-supported countries.
	 */
	public function is_country_supported( string $country ): bool {
		return array_key_exists(
			strtoupper( $country ),
			$this->get_mc_supported_countries_data()
		);
	}

	/**
	 * Find the ISO 3166-1 code of the Merchant Center supported country by its location ID.
	 *
	 * @param int $id
	 *
	 * @return string|null ISO 3166-1 representation of the country code.
	 */
	public function find_country_code_by_id( int $id ): ?string {
		return $this->get_country_id_code_map()[ $id ] ?? null;
	}

	/**
	 * Find the code of the Merchant Center supported subdivision by its location ID.
	 *
	 * @param int $id
	 *
	 * @return string|null
	 */
	public function find_subdivision_code_by_id( int $id ): ?string {
		return $this->get_subdivision_id_code_map()[ $id ] ?? null;
	}

	/**
	 * Find and return the location id for the given country code.
	 *
	 * @param string $code
	 *
	 * @return int|null
	 */
	public function find_country_id_by_code( string $code ): ?int {
		$countries = $this->get_mc_supported_countries_data();

		if ( isset( $countries[ $code ] ) ) {
			return $countries[ $code ]['id'];
		}

		return null;
	}

	/**
	 * Find and return the location id for the given subdivision (state, province, etc.) code.
	 *
	 * @param string $code
	 * @param string $country_code
	 *
	 * @return int|null
	 */
	public function find_subdivision_id_by_code( string $code, string $country_code ): ?int {
		return self::COUNTRY_SUBDIVISIONS[ $country_code ][ $code ]['id'] ?? null;
	}

	/**
	 * Returns an array mapping the ID of the Merchant Center supported countries to their respective codes.
	 *
	 * @return string[] Array of country codes with location IDs as keys. e.g. [ 2840 => 'US' ]
	 */
	protected function get_country_id_code_map(): array {
		if ( isset( $this->country_id_code_map ) ) {
			return $this->country_id_code_map;
		}
		$this->country_id_code_map = [];

		$countries = $this->get_mc_supported_countries_data();
		foreach ( $countries as $country ) {
			$this->country_id_code_map[ $country['id'] ] = $country['code'];
		}

		return $this->country_id_code_map;
	}

	/**
	 * Returns an array mapping the ID of the Merchant Center supported subdivisions to their respective codes.
	 *
	 * @return string[] Array of subdivision codes with location IDs as keys. e.g. [ 20035 => 'NSW' ]
	 */
	protected function get_subdivision_id_code_map(): array {
		if ( isset( $this->subdivision_id_code_map ) ) {
			return $this->subdivision_id_code_map;
		}
		$this->subdivision_id_code_map = [];

		foreach ( self::COUNTRY_SUBDIVISIONS as $subdivisions ) {
			foreach ( $subdivisions as $item ) {
				$this->subdivision_id_code_map[ $item['id'] ] = $item['code'];
			}
		}

		return $this->subdivision_id_code_map;
	}
}
