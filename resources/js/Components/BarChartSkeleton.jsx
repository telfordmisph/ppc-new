import clsx from "clsx";

const BarChartSkeleton = ({ bars = 9, className = "" }) => {
    const heights = Array.isArray(bars)
        ? bars.map((v) => Math.max(5, Math.min(100, v)))
        : Array.from(
              { length: bars },
              (_, i) => 20 + Math.round((i / Math.max(1, bars - 1)) * 80)
          );

    return (
        <div
            className={clsx(
                "flex flex-col w-full h-full relative p-4 bg-base-100 rounded-lg shadow-sm",
                className
            )}
            role="img"
            aria-label="Loading bar chart"
        >
            <div className="w-3/6 mb-3 h-[10%]">
                <div className="h-full rounded-md skeleton" />
            </div>

            <div className="flex items-end flex-grow gap-3 animate-pulse">
                {heights.map((pct, idx) => (
                    <div
                        key={idx}
                        className="h-full flex-1 min-w-[18px] flex flex-col justify-end"
                    >
                        <div
                            className="w-full skeleton rounded-t-md"
                            style={{ height: `${pct}%` }}
                            aria-hidden="true"
                        />
                        <div className="mt-2">
                            <div className="w-3/5 h-2 mx-auto rounded-md skeleton" />
                        </div>
                    </div>
                ))}
            </div>

            <div className="flex justify-center items-end mt-3 text-xs h-[10%] opacity-60">
                <div className="w-1/6 h-3 rounded-md skeleton" />
            </div>
        </div>
    );
};

export default BarChartSkeleton;
