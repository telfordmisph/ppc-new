import clsx from "clsx";

const BarChartSkeleton = ({ bars = 5, className = "" }) => {
    const heights = Array.from(
        { length: bars },
        (_, i) => 20 + Math.round((i / Math.max(1, bars - 1)) * 100)
    );

    return (
        <div
            className={clsx(
                "flex flex-col items-center p-4 w-64 h-64 relative my-32",
                className
            )}
            role="img"
            aria-label="Loading bar chart"
        >
            <div className="flex items-end justify-between flex-grow w-full h-full gap-4 animate-pulse">
                {heights.map((pct, idx) => (
                    <div
                        key={idx}
                        className="flex flex-col justify-end flex-1 h-full"
                    >
                        <div
                            className="w-full rounded-md skeleton"
                            style={{ height: `${pct}%` }}
                            aria-hidden="true"
                        />
                    </div>
                ))}
            </div>
        </div>
    );
};

export default BarChartSkeleton;
