import { useState, useRef, useEffect } from "react";

export function useMutation() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [data, setData] = useState(null);
  const abortControllerRef = useRef(null);

  const mutate = async (url, options = {}) => {
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const { method = "POST", body } = options;
      console.log("🚀 body:", body)
      console.log("🚀 vvv:", JSON.stringify(body))

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

      console.log("🚀 ~ mutate ~ resulasfsadfjldkjt:")

      if (!response.ok) {
        const text = await response.text();
        console.error("Server response:", response.status, text);
        throw new Error(`HTTP error ${response.status}`);
      }
      
      const result = await response.json();
      console.log("🚀 ~ mutate ~ result:", result)
      setData(result);
      return result;
    } catch (err) {
      if (err.name === "AbortError") {
        console.log("Request canceled");
      } else {
        setError(err);
        throw err;
      }
    } finally {
      setLoading(false);
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

  return { mutate, data, error, loading, cancel };
}
