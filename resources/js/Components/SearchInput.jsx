import clsx from "clsx";
import React, { useEffect, useState } from "react";
import { FaSearch } from "react-icons/fa";
import { MdClose } from "react-icons/md";

const SearchInput = React.memo(function SearchInput({
	inputClassName = "",
	placeholder = "search",
	initialSearchInput,
	onSearchChange,
}) {
	const [searchInput, setSearchInput] = useState(initialSearchInput);

	useEffect(() => {
		const handler = setTimeout(() => {
			onSearchChange(searchInput);
		}, 300);

		return () => clearTimeout(handler);
	}, [searchInput, onSearchChange]);

	return (
		<label className={clsx("input h-7.5 ml-auto", inputClassName)}>
			<FaSearch className="mr-2" />
			<input
				type="text"
				placeholder={placeholder}
				value={searchInput}
				onChange={(e) => setSearchInput(e.target.value)}
			/>
			{searchInput && (
				<button
					type="button"
					onClick={() => setSearchInput("")}
					className="ml-2 opacity-50 hover:opacity-100"
				>
					<MdClose />
				</button>
			)}
		</label>
	);
});

export default SearchInput;
