import { useState } from "react";
import { FaTimes } from "react-icons/fa";

export default function MultiInputList({
	selectedItems = [],
	ItemLabel = "",
	onChange,
}) {
	const [inputValue, setInputValue] = useState("");
	const [items, setItems] = useState(selectedItems);

	const handleAdd = () => {
		const trimmed = inputValue.trim();
		if (trimmed && !items.includes(trimmed)) {
			const newItems = [...items, trimmed];
			setItems(newItems);
			onChange(newItems);
			setInputValue("");
		}
	};

	const handleRemove = (index) => {
		const newItems = items.filter((_, i) => i !== index);
		setItems(newItems);
		onChange(newItems);
	};

	const handleEdit = (index, value) => {
		const newItems = [...items];
		newItems[index] = value;
		setItems(newItems);
		onChange(newItems);
	};

	const handleKeyDown = (e) => {
		if (e.key === "Enter") {
			e.preventDefault();
			handleAdd();
		}
	};

	return (
		<div>
			<div className="flex gap-2 items-center">
				<input
					type="text"
					value={inputValue}
					onChange={(e) => setInputValue(e.target.value)}
					onKeyDown={handleKeyDown}
					className="input w-50"
					placeholder={`Enter ${ItemLabel || "item"}`}
				/>
				<button
					type="button"
					className="btn btn-sm btn-secondary"
					onClick={handleAdd}
					disabled={!inputValue}
				>
					Add
				</button>
				<span>
					{selectedItems.length === 0 &&
						inputValue &&
						"you must add this to proceed"}
				</span>
			</div>

			<ul className="flex mt-2 flex-col gap-2">
				{items.map((item, index) => (
					<li
						className="flex gap-2 justify-between items-center rounded-lg w-min min-w-50"
						key={index}
					>
						<input
							type="text"
							value={item}
							onChange={(e) => handleEdit(index, e.target.value)}
							className="input w-full"
						/>
						<button
							type="button"
							className="btn btn-sm btn-ghost"
							onClick={() => handleRemove(index)}
						>
							<FaTimes />
						</button>
					</li>
				))}
			</ul>
		</div>
	);
}
