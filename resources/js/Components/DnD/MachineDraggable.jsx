import { clsx } from "clsx";
import React, { useState } from "react";
import { BiDuplicate } from "react-icons/bi";
import { FaTrash } from "react-icons/fa6";
import { Tooltip } from "react-tooltip";
import getColorForMachine, {
	getBackgroundForMachine,
} from "./getColorForMachine";

const MachineDraggable = React.memo(function MachineDraggable({
	d,
	updateDraggable,
	isOverlay = false,
	dupeFunction = null,
	unassignFunction = null,
}) {
	const { color: textColor } = getColorForMachine(d.machineId);
	const bgColor = getBackgroundForMachine(d.machineId, 80); // 50% text lightness
	const [hovered, setHovered] = useState(false);
	const dupetooltipId = `${d.id}-anchor-dupe-tooltip`;
	const unassignTooltipId = `${d.id}-anchor-unassign-tooltip`;

	return (
		<label
			onMouseEnter={() => setHovered(true)}
			onMouseLeave={() => setHovered(false)}
			className={clsx(
				"w-full items-center min-w-30 h-full justify-center flex rounded-lg text-sm relative",
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
				className="flex-1 rounded no-spinner input border-0 text-right input-ghost"
				value={d.value}
				onChange={(e) => updateDraggable(d.id, "value", e.target.value)}
			/>

			<Tooltip anchorSelect={`.${dupetooltipId}`} place="top">
				duplicate
			</Tooltip>
			<Tooltip anchorSelect={`.${unassignTooltipId}`} place="top">
				unassign
			</Tooltip>

			<div
				className={clsx(
					"absolute left-0 -top-8 flex transition-all duration-200 ease-out",
					{
						"opacity-100 translate-y-0 pointer-events-auto":
							hovered && !isOverlay,
						"opacity-0 -translate-y-2 pointer-events-none":
							!hovered || isOverlay,
					},
				)}
			>
				{dupeFunction && (
					<button
						type="button"
						tabIndex={hovered && !isOverlay ? 0 : -1}
						className={clsx(
							dupetooltipId,
							"btn btn-square text-neutral bg-accent text-2xl rounded",
						)}
						onClick={() => {
							if (dupeFunction) {
								dupeFunction(d);
							}
						}}
					>
						<BiDuplicate size={20} />
					</button>
				)}
				{unassignFunction && (
					<button
						type="button"
						tabIndex={hovered && !isOverlay ? 0 : -1}
						className={clsx(
							unassignTooltipId,
							"btn btn-square text-neutral bg-error text-2xl rounded",
						)}
						onClick={() => {
							if (unassignFunction) {
								unassignFunction(d);
							}
						}}
					>
						<FaTrash size={20} />
					</button>
				)}
			</div>
		</label>
	);
});

export { MachineDraggable };
