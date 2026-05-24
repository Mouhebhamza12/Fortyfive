import tshirtImage from "../../assets/images/yeshirt.png";
import tshirtBackImage from "../../assets/images/tshritback.png";
import tshirtBlackImage from "../../assets/images/yeshirtblack.png";
import tshirtBlackBackImage from "../../assets/images/yeshirtblackback.png";
import yeface from "../../assets/images/ye2front.png";
import yefaceback from "../../assets/images/ye2back.png";
import yefaceblack from "../../assets/images/ye2bfront.png";
import yefaceblackback from "../../assets/images/ye2bback.png";
import yety from "../../assets/images/yety.png";
import yetyback from "../../assets/images/yetyback.png";
import yetyb from "../../assets/images/yetyb.png";
import yetybback from "../../assets/images/yetybback.png";

export const products = [
  {
    id: 1,
    slug: "college-dropout-tee",
    name: "College Dropout Tee",
    price: "3500 DA",
    image: tshirtImage,
    images: {
      White: [
        { src: tshirtImage, alt: "College Dropout Tee front in white", label: "Front" },
        { src: tshirtBackImage, alt: "College Dropout Tee back in white", label: "Back" },
      ],
      Black: [
        { src: tshirtBlackImage, alt: "College Dropout Tee front in black", label: "Front" },
        { src: tshirtBlackBackImage, alt: "College Dropout Tee back in black", label: "Back" },
      ],
    },
    color: "White",
    colors: ["White", "Black"],
    fit: "Relaxed fit",
    material: "240gsm cotton jersey",
    description:
      "Built for everyday wear with a straightforward fit, heavier hand feel, and clean front print.",
    details: [
      "Midweight cotton with a dry finish",
      "Dropped shoulder and relaxed body",
      "Screen print front graphic",
      "Made for daily rotation",
    ],
    sizes: ["S", "M", "L", "XL"],
  },
  {
    id: 2,
    slug: "YE Tee",
    name: "Ye Tee",
    price: "3500 DA",
    image: yeface,
    images: {
      White: [
        { src: yeface, alt: "Ye Tee front in white", label: "Front" },
        { src: yefaceback, alt: "Ye Tee back in white", label: "Back" },
      ],
      Black: [
        { src: yefaceblack, alt: "Ye Tee front in black", label: "Front" },
        { src: yefaceblackback, alt: "Ye Tee back in black", label: "Back" },
      ],
    },
    color: "White",
    colors: ["White", "Black"],
    fit: "Relaxed fit",
    material: "240gsm cotton jersey",
    description:
      "A clean everyday tee with a centered graphic, structured collar, and enough weight to hold its shape.",
    details: [
      "Cotton jersey with a denser feel",
      "Relaxed fit through the chest",
      "Printed front artwork",
      "Easy to wear year-round",
    ],
    sizes: ["S", "M", "L", "XL"],
  },
   {
    id: 3,
    slug: "Vultures-tee",
    name: "Vultures Tee",
    price: "3500 DA",
    image: yety,
    images: {
      White: [
        { src: yety, alt: "Vultures Tee front in white", label: "Front" },
        { src: yetyback, alt: "Vultures Tee back in white", label: "Back" },
      ],
      Black: [
        { src: yetyb, alt: "Vultures Tee front in black", label: "Front" },
        { src: yetybback, alt: "Vultures Tee back in black", label: "Back" },
      ],
    },
    color: "White",
    colors: ["White", "Black"],
    fit: "Relaxed fit",
    material: "240gsm cotton jersey",
    description:
      "A direct graphic tee with a clean cut, balanced proportions, and a heavier cotton base.",
    details: [
      "Substantial cotton weight",
      "Relaxed silhouette",
      "Front print application",
      "Built for regular wear",
    ],
    sizes: ["S", "M", "L", "XL"],
  },
];

export const getProductBySlug = (slug) =>
  products.find((product) => product.slug === slug);
