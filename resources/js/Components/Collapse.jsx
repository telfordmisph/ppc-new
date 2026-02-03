import clsx from "clsx";
import { useState } from "react";

export default function Collapse({
	title,
	children,
	isOpen: controlledOpen,
	onToggle,
	className,
	contentClassName,
}) {
	const baseClasses = [
		"collapse",
		"hover:border",
		"relative",
		"hover:border-base-content/20",
		"hover:bg-base-content/1",
		"rounded-t-none",
		"collapse-arrow",
		"border",
		"border-base-300",
	];

	const [internalOpen, setInternalOpen] = useState(false);
	const open = controlledOpen !== undefined ? controlledOpen : internalOpen;
	const stateClasses = open ? "collapse-open" : "collapse-close";

	const toggle = () => {
		if (onToggle) onToggle(!open);
		else setInternalOpen(!open);
	};

	const close = () => {
		if (onToggle) onToggle(false);
		else setInternalOpen(false);
	};

	return (
		<div tabIndex={0} className={clsx(baseClasses, stateClasses, className)}>
			<input type="checkbox" checked={open} onChange={toggle} />

			<button
				type="button"
				className={clsx("btn hover:bg-red-500 absolute z-10000 top-2 right-2", {
					hidden: !open,
				})}
				onClick={close}
			>
				close
			</button>

			<div className="collapse-title font-semibold after:start-5 after:end-auto pe-4 ps-12">
				{title}
			</div>

			<div
				className={clsx("collapse-content text-sm w-full", contentClassName)}
			>
				{children}
			</div>
		</div>
	);
}
