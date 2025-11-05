import { useState, useEffect } from "react";

export function useWip() {
  const [wip, setWip] = useState({});
  const [latest, setLatest] = useState(null);
  const [yesterday, setYesterday] = useState(null);
  const [trend, setTrend] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState(null);

  useEffect(() => {
    let mounted = true;

    async function fetchWip() {
      try {
        const response = await fetch(route("api.wip.today"));

        let json;
        try {
          json = await response.json();
        } catch (jsonErr) {
          const error = new Error("Invalid JSON response from server");
          error.status = response.status;
          throw error;
        }

        if (!response.ok || (json && json.status === "error")) {
          const error = new Error(json?.message || `HTTP error: ${response.status}`);
          error.status = response.status;
          error.data = json;
          throw error;
        }

        if (json.length > 0) {
          const sorted = [...json].sort(
            (a, b) => new Date(a.date) - new Date(b.date)
          );

          const withTrend = sorted.map((item, index, arr) => {
            if (index === 0) return { ...item, trend: 0 };
            const prev = arr[index - 1];
            let dayTrend = prev.total > 0
              ? ((item.total - prev.total) / prev.total) * 100
              : 0;

            dayTrend = Math.round(dayTrend * 100) / 100;
            return { ...item, trend: dayTrend };
          });

          const wipObject = withTrend.reduce((acc, item) => {
            acc[item.date] = item;
            return acc;
          }, {});

          if (mounted) {
            setWip(wipObject);
            console.log("🚀 ~ fetchWip ~ wipObject:", wipObject)

            const todayData = withTrend[withTrend.length - 1];
            const yesterdayData = withTrend.length > 1 ? withTrend[withTrend.length - 2] : null;

            setLatest(todayData);
            setYesterday(yesterdayData);
            setTrend(todayData.trend);

            setIsLoading(false);
          }
        } else {
          throw new Error("No WIP data available");
        }
      } catch (err) {
          setErrorMessage(err.message);
          setIsLoading(false);
      }
    }

    fetchWip();

    return () => {
      mounted = false;
    };
  }, []);

  return { wip, latest, yesterday, trend, isLoading, errorMessage };
}
