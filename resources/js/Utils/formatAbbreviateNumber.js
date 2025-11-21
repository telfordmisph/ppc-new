/**
 * Formats large numbers into human-readable abbreviations using
 * metric-like suffixes (K, M, B, T, Q).
 *
 * - Handles thousands, millions, billions, trillions, and quadrillions.
 * - Automatically promotes (e.g. 999.9M → 1B).
 * - Caps at "999Q+" for anything ≥ 999 quadrillion.
 * - Preserves negative values and includes commas for small numbers.
 *
 * Examples:
 *   formatAbbreviateNumber(532)                  => "532"
 *   formatAbbreviateNumber(1_523)                => "1.5K"
 *   formatAbbreviateNumber(999_999)              => "1M"
 *   formatAbbreviateNumber(123_456_789)          => "123.5M"
 *   formatAbbreviateNumber(9_876_543_210)        => "9.9B"
 *   formatAbbreviateNumber(1_000_000_000_000)    => "1T"
 *   formatAbbreviateNumber(1_234_000_000_000_000)=> "1.2Q"
 *   formatAbbreviateNumber(999_000_000_000_000_000)=> "999Q"
 *   formatAbbreviateNumber(1_000_000_000_000_000_000)=> "999Q+"
 *   formatAbbreviateNumber(-1_500_000)           => "-1.5M"
 *
 * @param {number} num - The numeric value to format.
 * @returns {string} A formatted string representing the shortened number.
 */
const formatAbbreviateNumber = (num) => {
  const absNum = Math.abs(num);

  if (absNum < 1_000) return num.toLocaleString();

  const units = [
    { value: 1_000_000_000_000_000, symbol: "Q" }, // Quadrillion
    { value: 1_000_000_000_000, symbol: "T" },     // Trillion
    { value: 1_000_000_000, symbol: "B" },         // Billion
    { value: 1_000_000, symbol: "M" },             // Million
    { value: 1_000, symbol: "K" },                 // Thousand
  ];

  for (let i = 0; i < units.length; i++) {
    const unit = units[i];

    // Cap extremely large numbers at 999Q+
    if (absNum >= 999_000_000_000_000_000) {
      return (num < 0 ? "-" : "") + "999Q+";
    }

    if (absNum >= unit.value) {
      const v = num / unit.value;
      const rounded = Math.round(v * 10) / 10;

      // Promote to next unit if rounding exceeds 999.9 of current
      if (rounded >= 1000 && i > 0) {
        const higherUnit = units[i - 1];
        const hv = num / higherUnit.value;
        const hrounded = Math.round(hv * 10) / 10;
        const cleanH = hrounded % 1 === 0 ? Math.round(hrounded) : hrounded;
        return cleanH + higherUnit.symbol;
      }

      const clean = rounded % 1 === 0 ? Math.round(rounded) : rounded;
      return clean + unit.symbol;
    }
  }

  return num.toLocaleString();
};

export default formatAbbreviateNumber;


const testCases = [
  0,
  12,
  999,
  1000,
  1523,
  15_230,
  999_999,
  1_000_000,
  1_234_567,
  9_876_543,
  123_456_789,
  999_999_999,
  1_000_000_000,
  -1_000_000_000,
  2_345_678_901,
  45_678_901_234,
  999_999_999_999,
  1_000_000_000_000,
  5_678_901_234_567,
  12_345_678_901_234,
  999_999_999_999_999,
  1_000_000_000_000_000
];

// testCases.forEach((num, index) => {
//   console.log(`${index + 1}. ${num.toLocaleString()} => ${formatAbbreviateNumber(num)}`);
// });