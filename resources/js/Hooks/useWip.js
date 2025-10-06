import { useState, useEffect } from "react";

export function useWip() {
  const [wip, setWip] = useState({});
  const [latest, setLatest] = useState(null);
  const [yesterday, setYesterday] = useState(null);
  const [trend, setTrend] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    let mounted = true;

    async function fetchWip() {
      try {
        const res = await fetch("/api/today-wip");
        if (!res.ok) throw new Error("Failed to fetch WIP data");
        const json = await res.json();

        if (json.length > 0) {
          const sorted = [...json].sort(
            (a, b) => new Date(a.date) - new Date(b.date)
          );

          const withTrend = sorted.map((item, index, arr) => {
            if (index === 0) return { ...item, trend: 0 };
            const prev = arr[index - 1];
            const dayTrend = prev.total > 0
              ? ((item.total - prev.total) / prev.total) * 100
              : 0;
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

            setLoading(false);
          }
        } else {
          throw new Error("No WIP data available");
        }
      } catch (err) {
        if (mounted) {
          setError(err);
          setLoading(false);
        }
      }
    }

    fetchWip();

    return () => {
      mounted = false;
    };
  }, []);

  return { wip, latest, yesterday, trend, loading, error };
}
