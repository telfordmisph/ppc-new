/**
 * Utility: sortArrayOfObject
 * -----------------------------------------------------
 * Features:
 *  - Supports single or multi-key sorting
 *  - Supports computed sorting via a custom `compute` function
 *  - Automatically falls back to `keys` if `compute` is null
 *  - Handles ascending or descending order
 *  - Gracefully handles null, undefined, or non-numeric values
 *  - Does not mutate the original array
 *
 * @param {Array<Object>} data - The array of objects to sort.
 * @param {Object} sorter - Sorting options.
 * @param {string[]} [sorter.keys=['total_wip']] - Keys to sort by.
 * @param {'asc'|'desc'} [sorter.order='asc'] - Sort order.
 * @param {Function|null} [sorter.compute=null] - Function returning a numeric value for sorting.
 *
 * @returns {Array<Object>} A new sorted array.
 */

function sortObjectArray(
	data = [],
	sorter = { keys: ["total wip"], order: "asc", compute: null },
) {
	if (!Array.isArray(data)) return [];

	const { keys = ["total wip"], order = "asc", compute = null } = sorter;

	return [...data].sort((a, b) => {
		let valA, valB;

		// 1. Use compute if provided
		if (typeof compute === "function") {
			valA = compute(a);
			valB = compute(b);
		} else {
			// 2. Otherwise, compute value from one or more keys
			valA = keys.reduce((sum, key) => sum + Number(a[key] ?? 0), 0);
			valB = keys.reduce((sum, key) => sum + Number(b[key] ?? 0), 0);
		}

		// 3. Perform comparison
		if (valA < valB) return order === "asc" ? -1 : 1;
		if (valA > valB) return order === "asc" ? 1 : -1;
		return 0;
	});
}

export default sortObjectArray;
