import { ref } from "vue";
import { apiFetch } from "../lib/api";

export function useCheckoutShipping() {
  const wilayas = ref([]);
  const bureaus = ref([]);
  const quote = ref(null);

  const loadingWilayas = ref(false);
  const loadingBureaus = ref(false);
  const loadingQuote = ref(false);
  const error = ref(null);

  const loadWilayas = async () => {
    if (wilayas.value.length) {
      return;
    }

    loadingWilayas.value = true;
    error.value = null;

    try {
      const response = await apiFetch("/wilayas");
      if (!response.ok) {
        throw new Error("Could not load wilayas.");
      }
      wilayas.value = await response.json();
    } catch (err) {
      error.value = err.message || "Could not load delivery areas.";
      wilayas.value = [];
    } finally {
      loadingWilayas.value = false;
    }
  };

  const loadBureaus = async (wilayaId) => {
    bureaus.value = [];
    if (!wilayaId) {
      return;
    }

    loadingBureaus.value = true;
    error.value = null;

    try {
      const response = await apiFetch(`/wilayas/${wilayaId}/bureaus`);
      if (!response.ok) {
        throw new Error("Could not load pickup locations.");
      }
      bureaus.value = await response.json();
    } catch (err) {
      error.value = err.message || "Could not load pickup locations.";
      bureaus.value = [];
    } finally {
      loadingBureaus.value = false;
    }
  };

  const fetchQuote = async (wilayaId, deliveryType) => {
    quote.value = null;
    if (!wilayaId || !deliveryType) {
      return;
    }

    loadingQuote.value = true;
    error.value = null;

    try {
      const params = new URLSearchParams({
        wilaya_id: String(wilayaId),
        delivery_type: deliveryType,
      });
      const response = await apiFetch(`/shipping/quote?${params}`);
      if (!response.ok) {
        throw new Error("Could not calculate shipping.");
      }
      quote.value = await response.json();
    } catch (err) {
      error.value = err.message || "Could not calculate shipping.";
      quote.value = null;
    } finally {
      loadingQuote.value = false;
    }
  };

  return {
    wilayas,
    bureaus,
    quote,
    loadingWilayas,
    loadingBureaus,
    loadingQuote,
    error,
    loadWilayas,
    loadBureaus,
    fetchQuote,
  };
}
