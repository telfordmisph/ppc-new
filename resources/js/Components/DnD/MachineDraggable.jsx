import { clsx } from "clsx";
import React, { useState } from "react";
import { BiDuplicate } from "react-icons/bi";
import { BsArrowDown } from "react-icons/bs";
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
  const bgColor = getBackgroundForMachine(d.machineId, 80);
  const [hovered, setHovered] = useState(false);
  
  const [localValue, setLocalValue] = useState(d.value);

  React.useEffect(() => {
    setLocalValue(d.value);
  }, [d.value]);

  const dupetooltipId = `${d.id}-anchor-dupe-tooltip`;
  const unassignTooltipId = `${d.id}-anchor-unassign-tooltip`;

  const handleBlur = () => {
    if (Number(localValue) !== Number(d.value)) {
      updateDraggable(d.id, "value", localValue);
    }
  };

  return (
    <label
      onMouseEnter={() => setHovered(true)}
      onMouseLeave={() => setHovered(false)}
      className={clsx(
        "w-full items-center min-w-30 h-full justify-center flex rounded-lg text-sm relative",
        { "ring ring-accent": hovered && !isOverlay }
      )}
    >
      <div className="shrink-0 text-xs pl-1 rounded opacity-75">
        {d.name}
      </div>
      
      <input
        type="number"
        className="flex-1 rounded no-spinner input border-0 text-right input-ghost"
        value={localValue} 
        onChange={(e) => setLocalValue(e.target.value)}
        onBlur={handleBlur}
        onKeyDown={(e) => {
          if (e.key === "Enter") e.currentTarget.blur();
        }}
      />

      <div
        className={clsx(
          "absolute left-0 -top-8 flex transition-all duration-200 ease-out",
          {
            "opacity-100 translate-y-0 pointer-events-auto": hovered && !isOverlay,
            "opacity-0 -translate-y-2 pointer-events-none": !hovered || isOverlay,
          }
        )}
      >
        {dupeFunction && (
          <button
            type="button"
            className={clsx(dupetooltipId, "btn btn-square text-neutral bg-accent text-2xl rounded")}
            onClick={() => dupeFunction(d)}
          >
            <BiDuplicate size={20} />
          </button>
        )}
        {unassignFunction && (
          <button
            type="button"
            className={clsx(unassignTooltipId, "btn btn-square text-neutral bg-error text-2xl rounded")}
            onClick={() => unassignFunction(d)}
          >
            <FaTrash size={20} />
          </button>
        )}
        {d?.isDuplicate && (
          <div className="text-xs text-pink-500 flex items-center gap-1">
            this is a copy <BsArrowDown />
          </div>
        )}
      </div>
      
      <Tooltip anchorSelect={`.${dupetooltipId}`} place="top">duplicate</Tooltip>
      <Tooltip anchorSelect={`.${unassignTooltipId}`} place="top">unassign</Tooltip>
    </label>
  );
});

export { MachineDraggable };
