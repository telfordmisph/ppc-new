import { useState, useEffect, useRef } from "react";

export function useFetch(url, options = {}) {
  const { params = {}, auto = true } = options;
  
  const [data, setData] = useState(null);
  const [isLoading, setIsLoading] = useState(auto);
  const [errorMessage, setErrorMessage] = useState(null);
  const abortControllerRef = useRef(null);

  const buildUrlWithParams = (baseUrl, params) => {
    const query = new URLSearchParams(params).toString();
    return query ? `${baseUrl}?${query}` : baseUrl;
  };
  
  const fetchData = async (currentParams = params) => {
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }
    
    const controller = new AbortController();
    abortControllerRef.current = controller;
    
    setIsLoading(true);
    setErrorMessage(null);
    
    try {
      const fetchUrl = buildUrlWithParams(url, currentParams);
      console.log("🚀 ~ fetchData ~ fetchUrl:", fetchUrl)

      const response = await fetch(fetchUrl, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
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
      
      console.log("🚀 ~ fetchData ~ result:", result)
      setData(result);
    } catch (error) {
      console.log("🚀 ~ fetchData ~ err:", error)
      if (error.name !== "AbortError") {
        setErrorMessage(error.message);
      }
    } finally {
      setIsLoading(false);
    }
  };  

  useEffect(() => {
    if (auto) {
      fetchData(params);
    }

    return () => {
      abortControllerRef.current?.abort();
    };
  }, [auto]);

  return {
    data, 
    isLoading, 
    errorMessage, 
    fetch: (overrideParams = params) => fetchData(overrideParams), 
    abort: () => abortControllerRef.current?.abort() 
  };
}
