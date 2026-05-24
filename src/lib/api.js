export const apiBaseUrl =
  (import.meta.env.VITE_API_BASE_URL || "/api").replace(/\/$/, "");

export const appBaseUrl = apiBaseUrl.replace(/\/api$/, "");

export const jsonHeaders = {
  Accept: "application/json",
};

export const authJsonHeaders = {
  ...jsonHeaders,
  "Content-Type": "application/json",
};

export const apiFetch = (path, options = {}) =>
  fetch(`${apiBaseUrl}${path}`, {
    credentials: "include",
    ...options,
    headers: {
      ...jsonHeaders,
      ...(options.headers || {}),
    },
  });
