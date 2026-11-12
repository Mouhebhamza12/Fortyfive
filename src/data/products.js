/*
 * Fortyfive catalogue.
 *
 * "Atlas" is a working name and 3900 DA a working price: change both to the real ones.
 * Add `specs` (e.g. { label: "Lens", value: "UV400" }) only once your supplier confirms them.
 */
import blackSmoke from "../../assets/images/atlas/black-smoke.png";
import blackRose from "../../assets/images/atlas/black-rose.png";
import silverSmoke from "../../assets/images/atlas/silver-smoke.png";
import blackTea from "../../assets/images/atlas/black-tea.png";
import clear from "../../assets/images/atlas/clear.png";

const view = (src, color) => [
  { src, alt: `Atlas wrap glasses, ${color.toLowerCase()}`, label: color },
];

export const products = [
  {
    id: 1,
    slug: "atlas",
    name: "Atlas",
    category: "Eyewear",
    price: "3900 DA",
    image: blackSmoke,
    images: {
      "Black / Smoke": view(blackSmoke, "Black / Smoke"),
      "Black / Rose": view(blackRose, "Black / Rose"),
      "Silver / Smoke": view(silverSmoke, "Silver / Smoke"),
      "Black / Tea": view(blackTea, "Black / Tea"),
      Clear: view(clear, "Clear"),
    },
    color: "Black / Smoke",
    colors: ["Black / Smoke", "Black / Rose", "Silver / Smoke", "Black / Tea", "Clear"],
    // [frame, lens]
    swatches: {
      "Black / Smoke": ["#111111", "#3b3d40"],
      "Black / Rose": ["#111111", "#d9547c"],
      "Silver / Smoke": ["#b9bcbf", "#3b3d40"],
      "Black / Tea": ["#111111", "#d8c0b4"],
      Clear: ["#eef0f1", "#ffffff"],
    },
    shape: "Wrap",
    description: "A low wraparound frame that sits close and stays put on the move.",
    specs: [],
    sizes: ["One size"],
  },
];

export const getProductBySlug = (slug) =>
  products.find((product) => product.slug === slug);
