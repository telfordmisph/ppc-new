import clsx from "clsx";
import { useState } from "react";
import { FaCaretDown, FaTimes } from "react-icons/fa";

/**
 * MultiSelectDropdown Component
 * @param {Array} options - list of string options
 * @param {Array} value - currently selected options
 * @param {Function} onChange - callback when selection changes
 * @param {string} className - extra class for wrapper
 * @param {string} buttonClassName - class for dropdown button
 * @param {string} dropdownClassName - class for dropdown menu
 * @param {string} chipClassName - class for chips
 * @param {string} clearButtonClassName - class for clear all button
 */
export default function MultiSelectDropdown({
	options = [],
	value = [],
	onChange,
	className = "",
	buttonClassName = "btn w-full justify-between rounded-lg",
	dropdownClassName = "dropdown-content rounded-lg menu p-2 shadow bg-base-100 w-full",
	chipClassName = "badge badge-accent gap-1 cursor-pointer",
	clearButtonClassName = "badge badge-error gap-1 cursor-pointer",
	selectLabel = ["Select Options", "Modify Selection"],
}) {
	const [open, setOpen] = useState(false);

	const toggleOption = (option) => {
		if (value.includes(option)) {
			onChange(value.filter((o) => o !== option));
		} else {
			onChange([...value, option]);
		}
	};

	const clearAll = () => {
		onChange([]);
	};

	return (
		<div className={`${className}`}>
			<div className={clsx("flex flex-wrap gap-2", value.length > 0 && "mb-2")}>
				{value.map((option) => (
					<span key={option} className={chipClassName}>
						{option}
						<button
							type="button"
							onClick={() => toggleOption(option)}
							className="focus:outline-none rounded-lg"
						>
							<FaTimes size={14} />
						</button>
					</span>
				))}

				{value.length > 0 && (
					<button
						type="button"
						onClick={clearAll}
						className={clearButtonClassName}
					>
						Clear All
					</button>
				)}
			</div>

			<div className="w-full dropdown">
				<div
					tabIndex={0}
					role="button"
					className={buttonClassName}
					onClick={() => setOpen(!open)}
				>
					{value.length > 0 ? selectLabel[1] : selectLabel[0]}
					<FaCaretDown />
				</div>

				<ul tabIndex={0} className={dropdownClassName}>
					{options.map((option) => (
						<li key={option}>
							<label className="justify-start gap-2 cursor-pointer label">
								<input
									type="checkbox"
									className="checkbox checkbox-sm"
									checked={value.includes(option)}
									onChange={() => toggleOption(option)}
								/>
								<span>{option}</span>
							</label>
						</li>
					))}
				</ul>
			</div>
		</div>
	);
}
