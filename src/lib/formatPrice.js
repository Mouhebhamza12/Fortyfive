export const formatPrice = (value) => {
  const amount = Number(value) || 0;
  return `${Math.round(amount).toLocaleString("fr-DZ")} DA`;
};

export const parsePrice = (price) => {
  const numeric = Number.parseInt(String(price).replace(/[^\d]/g, ""), 10);
  return Number.isFinite(numeric) ? numeric : 0;
};
