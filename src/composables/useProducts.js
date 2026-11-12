import { computed, ref } from "vue";
import { products as staticProducts } from "../data/products";
import { apiFetch } from "../lib/api";
import { formatPrice, parsePrice } from "../lib/formatPrice";

const catalog = ref([]);
const isLoading = ref(false);
const isReady = ref(false);
let loadPromise = null;

const staticCatalog = () =>
  staticProducts.map((product) => {
    const priceValue = parsePrice(product.price);
    return { ...product, priceValue, price: formatPrice(priceValue), in_stock: true };
  });

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
    color: apiProduct.colors?.[0] ?? staticProduct?.color ?? "",
    colors: apiProduct.colors?.length
      ? apiProduct.colors
      : staticProduct?.colors ?? [],
    swatches: staticProduct?.swatches ?? {},
    category: apiProduct.category || staticProduct?.category || "",
    shape: staticProduct?.shape ?? "",
    description: apiProduct.description || staticProduct?.description || "",
    specs: staticProduct?.specs ?? [],
    sizes: apiProduct.sizes?.length
      ? apiProduct.sizes
      : staticProduct?.sizes ?? ["One size"],
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
          catalog.value = staticCatalog();
        }
      } else {
        catalog.value = staticCatalog();
      }
    } catch {
      catalog.value = staticCatalog();
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
    staticCatalog().find((product) => product.slug === slug);

  return {
    products,
    isLoading,
    isReady,
    loadProducts,
    getProductBySlug,
  };
};
