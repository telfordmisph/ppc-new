function hslToHex(h, s, l) {
  const a = s * Math.min(l, 1 - l);
  const f = (n) => {
    const k = (n + h / 30) % 12;
    const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
    return Math.round(255 * color).toString(16).padStart(2, "0");
  };
  return `#${f(0)}${f(8)}${f(4)}`;
}

function generateDistinctColors(n) {
  const GOLDEN_RATIO = 0.618033988749895;

  // Vary sat/lightness across layers so colors don't blur together at high N
  const layers = [
    [0.75, 0.50],
    [0.90, 0.35],
    [0.55, 0.65],
    [0.85, 0.70],
    [0.65, 0.30],
  ];

  const colors = [];
  let hue = 0;

  for (let i = 0; i < n; i++) {
    const [s, l] = layers[i % layers.length];
    colors.push(hslToHex(hue * 360, s, l));
    hue = (hue + GOLDEN_RATIO) % 1;
  }

  return colors;
}

export default generateDistinctColors;