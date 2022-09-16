const useCategoryTree = () => {
	return {
		data: [
			// todo: Dummy content. API Call to be implemented
			{
				value: 'Category 1',
				label: 'Category 1',
				children: [
					{
						value: 'Category 1.1',
						label: 'Category 1.1',
					},
					{
						value: 'Category 1.2',
						label: 'Category 1.2',
					},
				],
			},
			{
				value: 'Category 2',
				label: 'Category 2',
				children: [
					{
						value: 'Category 2.1',
						label: 'Category 2.1',
						children: [
							{
								value: 'Category 2.1.1',
								label: 'Category 2.1.1',
							},
							{
								value: 'Category 2.1.2',
								label: 'Category 2.1.2',
							},
						],
					},
					{
						value: 'Category 2.2',
						label: 'Category 2.2',
					},
				],
			},
		],
	};
};

export default useCategoryTree;
