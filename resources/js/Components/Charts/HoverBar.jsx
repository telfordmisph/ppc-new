import { useState, memo } from "react";
import { Rectangle } from "recharts";

const HoveredBar = memo(function HoveredBar({
    barProps,
    onClick,
    stroke = "var(--color-accent)",
    overlayColor = "rgba(255, 255, 255, 0.2)",
    strokeWidth = 2,
    hoverHeight = 999,
    radius = 4,
}) {
    const [isHovered, setIsHovered] = useState(false);
    const { fill, x, y, width, height, payload, dataKey } = barProps || {};

    const handleClick = (e) => {
        if (onClick) onClick({ event: e, data: payload, dataKey });
    };

    return (
        <g
            onClick={handleClick}
            // TODO: not working hover effects
            onMouseEnter={() => setIsHovered(true)}
            onMouseLeave={() => setIsHovered(false)}
            className="group cursor-pointer hover:bg-red-500"
        >
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
                strokeWidth={isHovered ? strokeWidth * 2 : 0}
            />

            {/* Overlay */}
            {/* {isHovered && (
                <Rectangle
                    width={width}
                    height={height}
                    x={x}
                    y={y}
                    radius={radius}
                    fill={overlayColor}
                />
            )} */}
        </g>
    );
});

HoveredBar.displayName = "HoveredBar";
export default HoveredBar;
