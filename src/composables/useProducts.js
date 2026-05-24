import { computed, ref } from "vue";
import { products as staticProducts } from "../data/products";
import { apiFetch } from "../lib/api";
import { formatPrice } from "../lib/formatPrice";

const catalog = ref([]);
const isLoading = ref(false);
const isReady = ref(false);
let loadPromise = null;

const mergeWithStatic = (apiProduct) => {
  const staticProduct = staticProducts.find(
    (product) => product.slug === apiProduct.slug,
  );

  const priceValue =
    typeof apiProduct.price === "number"
      ? apiProduct.price
      : Number.parseFloat(String(apiProduct.price)) || 0;

  return {
    id: apiProduct.id ?? staticProduct?.id,
    slug: apiProduct.slug,
    name: apiProduct.name,
    price: formatPrice(priceValue),
    priceValue,
    image:
      staticProduct?.image ??
      (typeof apiProduct.images?.[0] === "string"
        ? apiProduct.images[0]
        : apiProduct.images?.[0]?.src) ??
      "",
    images: staticProduct?.images ?? apiProduct.images ?? [],
    color: staticProduct?.color ?? apiProduct.colors?.[0] ?? "White",
    colors: apiProduct.colors?.length
      ? apiProduct.colors
      : staticProduct?.colors ?? ["White"],
    fit: staticProduct?.fit ?? "Relaxed fit",
    material: staticProduct?.material ?? "240gsm cotton jersey",
    description: apiProduct.description || staticProduct?.description || "",
    details: staticProduct?.details ?? [],
    sizes: apiProduct.sizes?.length
      ? apiProduct.sizes
      : staticProduct?.sizes ?? ["S", "M", "L", "XL"],
    featured: Boolean(apiProduct.featured),
    in_stock: apiProduct.in_stock !== false,
  };
};

const loadProducts = async () => {
  if (isReady.value) {
    return catalog.value;
  }

  if (loadPromise) {
    return loadPromise;
  }

  isLoading.value = true;

  loadPromise = (async () => {
    try {
      const response = await apiFetch("/products");

      if (response.ok) {
        const data = await response.json();

        if (Array.isArray(data) && data.length) {
          catalog.value = data.map(mergeWithStatic);
        } else {
          catalog.value = staticProducts.map((product) => ({
            ...product,
            priceValue:
              Number.parseInt(String(product.price).replace(/[^\d]/g, ""), 10) || 0,
          }));
        }
      } else {
        catalog.value = staticProducts.map((product) => ({
          ...product,
          priceValue:
            Number.parseInt(String(product.price).replace(/[^\d]/g, ""), 10) || 0,
        }));
      }
    } catch {
      catalog.value = staticProducts.map((product) => ({
        ...product,
        priceValue:
          Number.parseInt(String(product.price).replace(/[^\d]/g, ""), 10) || 0,
      }));
    } finally {
      isLoading.value = false;
      isReady.value = true;
      loadPromise = null;
    }

    return catalog.value;
  })();

  return loadPromise;
};

export const useProducts = () => {
  const products = computed(() => catalog.value);

  const getProductBySlug = (slug) =>
    catalog.value.find((product) => product.slug === slug) ??
    staticProducts.find((product) => product.slug === slug);

  return {
    products,
    isLoading,
    isReady,
    loadProducts,
    getProductBySlug,
  };
};
