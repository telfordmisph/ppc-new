import { throttle } from "es-toolkit";
import { useCallback, useMemo, useState } from "react";

// * from: https://recharts.github.io/en-US/guide/performance/
export default function useStateThrottle(defaultValue) {
	const [state, setState] = useState(defaultValue);
	const throttledSetState = useMemo(
		() =>
			throttle(
				(props) => {
					setState(
						Number.isNaN(props.activeTooltipIndex)
							? undefined
							: Number(props.activeTooltipIndex),
					);
				},
				/*
				 * Experiment with the delay time to find a good balance
				 * between responsiveness and performance.
				 * I find that delays below 100ms still feel "instant"
				 * and give the chart plenty of time to catch up.
				 */
				1000,
				{ edges: ["trailing"] },
			),
		[setState],
	);

	const clearState = useCallback(() => {
		setState(undefined);
	}, [setState]);
	return [state, throttledSetState, clearState];
}
