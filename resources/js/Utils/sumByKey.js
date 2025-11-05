/**
 * Groups and sums numeric properties of objects by a given key.
 *
 * @param {Array} data - The array of objects to group and sum.
 * @param {string} groupKey - The property name to group by (e.g., "Package_Name").
 * @param {Array<string>} excludeKeys - Optional keys to skip during summation (besides the group key).
 * @returns {Array} - New array with summed objects per group key.
 */
export function sumByKey(data = [], groupKey, excludeKeys = []) {
  if (!Array.isArray(data) || !groupKey) return [];

  const grouped = data.reduce((acc, obj) => {
    const key = obj[groupKey];
    if (!key) return acc;

    if (!acc[key]) {
      acc[key] = { [groupKey]: key };
    }

    for (const [prop, value] of Object.entries(obj)) {
      if (prop === groupKey || excludeKeys.includes(prop)) continue;

      const num = Number(value) || 0;
      acc[key][prop] = (acc[key][prop] || 0) + num;
    }

    return acc;
  }, {});

  return Object.values(grouped);
}
