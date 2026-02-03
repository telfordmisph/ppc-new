import React from "react";

const MaxItemDropdown = ({
	maxItem,
	changeMaxItemPerPage,
	maxItems = [10, 25, 50, 100],
}) => {
	return (
		<div className="dropdown dropdown-bottom">
			<div tabIndex={0} className="m-1 btn">
				{`Show ${maxItem} items`}
			</div>
			<ul
				tabIndex={0}
				className="p-2 shadow-lg dropdown-content menu bg-base-100 rounded-lg z-1 w-52"
			>
				{maxItems.map((item) => (
					<li key={item}>
						<button
							type="button"
							onClick={() => {
								changeMaxItemPerPage(item);
							}}
							className="flex items-center justify-between"
						>
							{item}
							{maxItem === item && (
								<span className="font-bold text-green-500">✔</span>
							)}
						</button>
					</li>
				))}
			</ul>
		</div>
	);
};

export default MaxItemDropdown;
