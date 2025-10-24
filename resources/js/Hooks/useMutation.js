import { useState, useRef, useEffect } from "react";

export function useMutation() {
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);
  const [data, setData] = useState(null);
  const abortControllerRef = useRef(null);

  const mutate = async (url, options = {}) => {
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setIsLoading(true);
    setErrorMessage(null);

    try {
      const { method = "POST", body } = options;

      const response = await fetch(url, {
        method,
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        },
        body: body ? JSON.stringify(body) : undefined,
        signal: controller.signal,
      });

      let result;
      try {
        result = await response.json();
      } catch (jsonErr) {
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
      
      setData(result);
      return result;
    } catch (error) {
      if (error.name !== "AbortError") {
        setErrorMessage(error.message);
        throw error;
      }
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    return () => {
      abortControllerRef.current?.abort();
    };
  }, []);


  const cancel = () => {
    abortControllerRef.current?.abort();
  };

  return { mutate, data, errorMessage, isLoading, cancel };
}
