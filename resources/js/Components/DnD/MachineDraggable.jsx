import { clsx } from "clsx";
import { useState } from "react";
import { Tooltip } from "react-tooltip";
import getColorForMachine, {
	getBackgroundForMachine,
} from "./getColorForMachine";

function MachineDraggable({
	d,
	updateDraggable,
	isOverlay = false,
	dupeFunction = () => {},
}) {
	const { color: textColor } = getColorForMachine(d.machineId);
	const bgColor = getBackgroundForMachine(d.machineId, 80); // 50% text lightness
	const [hovered, setHovered] = useState(false);

	return (
		<label
			onMouseEnter={() => setHovered(true)}
			onMouseLeave={() => setHovered(false)}
			className={clsx(
				"z-50 w-full items-center  h-full justify-center flex rounded-lg text-sm relative",
				{
					"ring ring-accent": hovered && !isOverlay,
				},
			)}
		>
			<div
				className="shrink-0 text-xs pl-1 rounded opacity-75"
				// style={{ color: textColor, backgroundColor: bgColor }}
			>
				{d.name}
			</div>
			<input
				type="number"
				className="flex-1 rounded input border-0 text-right input-ghost"
				value={d.value}
				onChange={(e) => updateDraggable(d.id, "value", e.target.value)}
			/>

			<Tooltip anchorSelect=".my-anchor-element" place="bottom">
				duplicate here
			</Tooltip>
			{hovered && !isOverlay && (
				<button
					type="button"
					className="my-anchor-element absolute btn-sm btn btn-square -left-6 bg-accent text-white px-2 py-1 rounded"
					onClick={() => {
						if (dupeFunction) {
							dupeFunction(d);
						}
					}}
				>
					X
				</button>
			)}
		</label>
	);
}

export { MachineDraggable };
