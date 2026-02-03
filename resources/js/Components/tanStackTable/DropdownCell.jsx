import React from "react";
import ContainerPortal from "../DropdownPortal";
import { FaCaretDown } from "react-icons/fa";
import clsx from "clsx";

const DropdownCell = React.memo(function StatusCell({
	statusOptions,
	value,
	rowIndex,
	columnId,
	onChange,
	buttonClassname = "",
}) {
	const buttonRef = React.useRef(null);
	const [open, setOpen] = React.useState(false);

	return (
		<>
			<button
				type="button"
				ref={buttonRef}
				className={clsx(
					"btn w-full bg-base-100 flex justify-between border-0",
					buttonClassname,
				)}
				onClick={() => setOpen((v) => !v)}
			>
				{value}
				<FaCaretDown />
			</button>

			<ContainerPortal
				ref={buttonRef}
				parentOpen={open}
				onClose={() => setOpen(false)}
			>
				<ul className="menu bg-base-100 rounded-box w-40 p-1 shadow-sm">
					{statusOptions.map((option) => (
						<li key={option}>
							<button
								type="button"
								onClick={() => {
									onChange(rowIndex, columnId, option);
									setOpen(false);
								}}
							>
								{option}
							</button>
						</li>
					))}
				</ul>
			</ContainerPortal>
		</>
	);
});

export default DropdownCell;
