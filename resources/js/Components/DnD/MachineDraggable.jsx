import { clsx } from "clsx";
import React, { useState } from "react";
import { BiDuplicate } from "react-icons/bi";
import { PiArrowBendRightDownFill } from "react-icons/pi";
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
				"z-50 w-full items-center min-w-30 h-full justify-center flex rounded-lg text-sm relative",
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

			<Tooltip anchorSelect={`.${dupetooltipId}`} place="bottom">
				duplicate
			</Tooltip>
			<Tooltip anchorSelect={`.${unassignTooltipId}`} place="bottom">
				unassign
			</Tooltip>

			{hovered && !isOverlay && (
				<div className="absolute z-50 left-0 -top-8">
					{dupeFunction && (
						<button
							type="button"
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
							<PiArrowBendRightDownFill size={20} />
						</button>
					)}
				</div>
			)}
		</label>
	);
});

export { MachineDraggable };
