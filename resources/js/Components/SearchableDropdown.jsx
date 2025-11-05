import clsx from "clsx";
import { useState } from "react";

export default function SearchableDropdown({
    selectedItem = null,
    onSelectItem,
    items = ["Uppercase", "Lowercase", "Camel Case", "Kebab Case"],
    isLoading = false,
    errorMessage = null,
    buttonClassName = "",
}) {
    const [searchTerm, setSearchTerm] = useState("");

    const filteredItems = items.filter(
        (item) =>
            typeof item === "string" &&
            item.toLowerCase().includes(searchTerm.toLowerCase().trim())
    );

    return (
        <div className="dropdown flex items-center justify-center">
            <div className="relative">
                <div
                    tabIndex={0}
                    role="button"
                    className={clsx("btn", buttonClassName)}
                >
                    {selectedItem || "Select Item"}
                </div>
                {isLoading === false ? (
                    <div className="dropdown-content menu bg-base-100 rounded-box z-1 w-52 p-2 shadow-sm">
                        <input
                            type="text"
                            placeholder="Search items"
                            autoComplete="off"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="block w-full px-4 py-2 border rounded-md border-base-content/30 focus:outline-none mb-2"
                        />
                        <ul tabIndex="-1" className="overflow-y-auto max-h-50">
                            {filteredItems.length > 0 ? (
                                filteredItems.map((item, idx) => (
                                    <li key={idx}>
                                        <a
                                            href="#"
                                            className="block px-4 py-2 cursor-pointer rounded-md"
                                            onClick={() => onSelectItem(item)}
                                        >
                                            {item}
                                        </a>
                                    </li>
                                ))
                            ) : (
                                <li>
                                    <div className="pointer-events-none px-4 py-2 text-sm">
                                        No results
                                    </div>
                                </li>
                            )}
                        </ul>
                    </div>
                ) : errorMessage === null ? (
                    <div className="dropdown-content menu bg-base-100 rounded-box z-1 w-52 p-2 shadow-sm">
                        <span className="loading loading-spinner"></span>
                    </div>
                ) : (
                    <div className="dropdown-content menu bg-base-100 rounded-box z-1 w-52 p-2 shadow-sm">
                        <div className="text-red-500">{errorMessage}</div>
                    </div>
                )}
            </div>
        </div>
    );
}
