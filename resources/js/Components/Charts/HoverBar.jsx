import { Rectangle } from "recharts";

export default function HoveredBar({
    barProps,
    onClick,
    stroke = "var(--color-accent)",
    overlayColor = "rgba(255, 255, 255, 0.2)",
    strokeWidth = 2,
    hoverHeight = 999,
    radius = 4,
}) {
    const { fill, x, y, width, height, payload, dataKey } = barProps || {};

    const handleClick = (e) => {
        if (onClick) onClick({ event: e, data: payload, dataKey });
    };

    return (
        <g onClick={handleClick} className="group cursor-pointer">
            {/* Transparent hover zone */}
            <Rectangle
                width={width}
                height={hoverHeight}
                x={x}
                fill="transparent"
                y={0}
            />

            {/* Main bar */}
            <Rectangle
                stroke={stroke}
                width={width}
                height={height}
                x={x}
                y={y}
                radius={radius}
                fill={fill}
                strokeWidth={strokeWidth}
            />

            {/* Overlay */}
            <Rectangle
                width={width}
                height={height}
                x={x}
                y={y}
                radius={radius}
                fill={overlayColor}
            />
        </g>
    );
}
