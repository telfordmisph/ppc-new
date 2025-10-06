import { useState, useEffect, useRef } from "react";

export function useFetch(url, options = {}) {
  const { params = {}, auto = true } = options;
  
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(auto);
  const [error, setError] = useState(null);
  const abortControllerRef = useRef(null);

  const buildUrlWithParams = (baseUrl, params) => {
    const query = new URLSearchParams(params).toString();
    return query ? `${baseUrl}?${query}` : baseUrl;
  };
  
  const fetchData = async (currentParams = params) => {
    console.log("🚀 ~ fetchData ~ currentParams:", currentParams)
    
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }
    
    const controller = new AbortController();
    abortControllerRef.current = controller;
    
    setLoading(true);
    setError(null);
    
    try {
      const fetchUrl = buildUrlWithParams(url, currentParams);

      const response = await fetch(fetchUrl, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
        signal: controller.signal,
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const result = await response.json();
      
      console.log("🚀 ~ r r e e s s u u l l t t:", result)
      setData(result);
    } catch (err) {
      if (err.name !== "AbortError") {
        setError(err);
      }
    } finally {
      setLoading(false);
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
    loading, 
    error, 
    fetch: (overrideParams = params) => fetchData(overrideParams), 
    abort: () => abortControllerRef.current?.abort() 
  };
}
