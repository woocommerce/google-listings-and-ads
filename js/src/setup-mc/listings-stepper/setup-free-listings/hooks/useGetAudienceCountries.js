// TODO: React hook to call backend API to get the selected countries
// from Step 2 Choose Your Audience.
const useGetAudienceCountries = () => {
	const audienceCountries = [
		{
			key: 'AUS',
			label: 'Australia',
		},
		{
			key: 'CHN',
			label: 'China',
		},
		{
			key: 'USA',
			label: 'United States of America',
		},
	];

	return audienceCountries;
};

export default useGetAudienceCountries;
