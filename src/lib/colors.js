// Swatch colours. A product can define its own (one colour, or a [frame, lens] pair);
// otherwise we fall back to the colour's name.
const named = {
  black: "#111111", white: "#ffffff", tortoise: "#6b3f1f", brown: "#5b3d22",
  gold: "#b9975b", silver: "#b9bcbf", grey: "#7d868a", gray: "#7d868a",
  blue: "#2f5d8a", navy: "#1c2f4a", green: "#3f5a45", red: "#a3362b",
  pink: "#d9547c", rose: "#d9547c", sand: "#d6c3a1", clear: "#eef0f1",
};

export const swatchStyle = (product, color) => {
  const own = product?.swatches?.[color];
  const [a, b] = Array.isArray(own)
    ? own
    : [own ?? named[String(color).toLowerCase()] ?? "#c9c9c9"];
  return { background: b ? `linear-gradient(135deg, ${a} 50%, ${b} 50%)` : a };
};
