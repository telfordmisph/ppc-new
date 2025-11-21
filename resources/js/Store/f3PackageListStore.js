import { create } from "zustand";

export const useF3PackagesStore = create((set, get) => {
  let abortController = null;

  const buildUrlWithParams = (baseUrl, params = { }) => {
    const query = new URLSearchParams(params).toString();
    return query ? `${baseUrl}?${query}` : baseUrl;
  };

  return {
    data: null,
    isLoading: false,
    isLoaded: false,
    errorMessage: null,

    async fetchF3PackageNames(params = {}) {
      if (get().isLoaded) {
        return;
      }

      if (abortController) {
        abortController.abort();
      }

      const controller = new AbortController();
      abortController = controller;

      set({ isLoading: true, errorMessage: null });

      try {
        const url = buildUrlWithParams(route("api.f3.package.names.getAll"), params);

        const response = await fetch(url, {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
          signal: abortController.signal,
        });

        let result;
        try {
          result = await response.json();
        } catch {
          const error = new Error("Invalid JSON response from server");
          error.status = response.status;
          throw error;
        }

        if (!response.ok || (result && result.status === "error")) {
          const error = new Error(result?.message || `HTTP error: ${response.status}`);
          error.status = response.status;
          error.data = result;
          throw error;
        }

        console.log("✅ F3 Package Names fetched:", result);
        if (controller === abortController) {
          set({ data: result });
          set({ isLoaded: true });
        }
      } catch (error) {
        if (error.name !== "AbortError") {
          console.log("❌ Fetch error:", error);
          set({ errorMessage: error.message });
        }
      } finally {
        console.log("Set loading FALSE");
        if (controller === abortController) {
          set({ isLoading: false });
        }
      }
    },

    abortFetch() {
      if (abortController) {
        abortController.abort();
        abortController = null;
        set({ isLoading: false });
      }
    },
  };
});
