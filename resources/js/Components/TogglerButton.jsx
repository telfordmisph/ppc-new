import clsx from "clsx";
import React, { memo } from "react";
import { FaTimes } from "react-icons/fa";
import { FaCheck } from "react-icons/fa6";

const TogglerButton = memo(function TogglerButton({
	id,
	toggleButtons,
	visibleBars,
	toggleBar,
	toggleAll = null,
	buttonClassName = "",
	singleSelect = false,
}) {
	const handleClick = (key) => {
		if (singleSelect) {
			const onlyThis = Object.fromEntries(
				toggleButtons.map((b) => [b.key, b.key === key]),
			);
			toggleBar(id, key, onlyThis);
		} else {
			toggleBar(id, key);
		}
	};

	return (
		<div className="join rounded-lg font-medium">
			{toggleButtons.map(({ key, label, activeClass, inactiveClass }) => (
				<button
					type="button"
					key={key}
					onClick={() => handleClick(key)}
					className={clsx(
						"join-item flex btn btn-sm text-sm items-center gap-x-2 px-3 py-1 transition-colors duration-200",
						visibleBars[key] ? activeClass : inactiveClass,
						buttonClassName,
					)}
				>
					{label}
					{visibleBars[key] ? <FaCheck /> : <FaTimes />}
				</button>
			))}

			{toggleAll && !singleSelect && (
				<button
					type="button"
					onClick={toggleAll}
					className="join-item px-3 py-1 btn btn-sm text-sm btn-outline"
				>
					Toggle All
				</button>
			)}
		</div>
	);
});

export default TogglerButton;
