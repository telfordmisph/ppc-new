import clsx from "clsx";
import { useState, useEffect, useMemo } from "react";
import { FaTimes } from "react-icons/fa";
import { Tooltip } from "react-tooltip";
import { memo } from "react";
import { EXCLUSIVE_OPTION_VALUES } from "@/Constants/exclusivePackageFilter";

const MultiSelectSearchableDropdown = memo(
    function MultiSelectSearchableDropdown({
        formFieldName,
        options = [],
        onChange,
        defaultSelectedOptions = [],
        isLoading = false,
        contentClassName = "",
        itemName = "options",
        prompt = "Select one or more options",
        debounceDelay = 200,
        singleSelect = false,
    }) {
        const [selectedOptions, setSelectedOptions] = useState(
            defaultSelectedOptions
        );
        const [searchInput, setSearchInput] = useState("");
        const [debouncedSearch, setDebouncedSearch] = useState("");

        useEffect(() => {
            const timer = setTimeout(
                () => setDebouncedSearch(searchInput),
                debounceDelay
            );
            return () => clearTimeout(timer);
        }, [searchInput, debounceDelay]);

        const handleChange = (e) => {
            const value = e.target.value;

            let updated;

            if (singleSelect) {
                updated = [value];
            } else {
                const isChecked = e.target.checked;

                if (EXCLUSIVE_OPTION_VALUES.includes(value)) {
                    updated = [value];
                } else {
                    updated = selectedOptions.filter(
                        (item) => !EXCLUSIVE_OPTION_VALUES.includes(item)
                    );
                    updated = isChecked
                        ? [...updated, value]
                        : updated.filter((item) => item !== value);
                }
            }

            setSelectedOptions(updated);
            onChange(updated);
        };

        // const handleChange = (e) => {
        //     const value = e.target.value;

        //     let updated;
        //     if (singleSelect) {
        //         updated = [value];
        //     } else {
        //         const isChecked = e.target.checked;
        //         updated = isChecked
        //             ? [...selectedOptions, value]
        //             : selectedOptions.filter((item) => item !== value);
        //     }

        //     setSelectedOptions(updated);
        //     onChange(updated);
        // };

        const handleClearSelectionClick = (e) => {
            e.preventDefault();
            setSelectedOptions([]);
            onChange([]);
        };

        const isClearSelectionEnabled = selectedOptions.length > 0;

        const getButtonLabel = () => {
            const count = selectedOptions.length;
            if (count === 0) return prompt;
            if (singleSelect || count === 1) return selectedOptions[0];
            const [first, ...rest] = selectedOptions;
            return `${first} and ${count - 1} more`;
        };

        const filteredOptions = useMemo(() => {
            if (!debouncedSearch.trim()) return options;

            const search = debouncedSearch.toLowerCase();

            return options.filter(
                (option) =>
                    option.value.toLowerCase().includes(search) ||
                    (option.label &&
                        option.label.toLowerCase().includes(search))
            );
        }, [debouncedSearch, options]);

        const highlightMatch = (option) => {
            const search = debouncedSearch.trim().toLowerCase();
            // if (!search) return option;

            const regex = new RegExp(`(${search})`, "i");

            const highlightText = (text) => {
                return text.split(regex).map((part, i) =>
                    part.toLowerCase() === search ? (
                        <span key={i} className="text-primary font-medium">
                            {part}
                        </span>
                    ) : (
                        <span key={i}>{part}</span>
                    )
                );
            };

            if (option.label && option.label !== option.value) {
                return (
                    <div className="flex items-center">
                        <div className="w-10">
                            {highlightText(option.value)}
                        </div>
                        <div className="opacity-75 text-xs">
                            {highlightText(option.label)}
                        </div>
                    </div>
                );
            }

            return (
                <span className="text-value">
                    {highlightText(option.value)}
                </span>
            );
        };

        const tooltipID = `${itemName}-tooltip`;

        return (
            <div className="dropdown h-full">
                <Tooltip
                    id={tooltipID}
                    className="custom-tooltip"
                    hidden={selectedOptions.length === 0}
                >
                    <div className="text-left p-2 flex flex-wrap gap-x-2 gap-y-1">
                        {selectedOptions.map((option) => (
                            <span
                                className="border border-neutral-content/40 px-1 rounded-lg"
                                key={option}
                            >
                                {option}
                            </span>
                        ))}
                    </div>
                </Tooltip>

                <div
                    data-tooltip-id={tooltipID}
                    tabIndex={0}
                    role="button"
                    className="btn w-52 border border-base-content/20"
                >
                    {getButtonLabel()}
                </div>

                <ul
                    tabIndex={-1}
                    className={clsx(
                        "dropdown-content flex flex-col bg-base-100 rounded-box p-2 shadow-sm",
                        contentClassName
                    )}
                >
                    {isLoading ? (
                        <div className="justify-center my-auto flex gap-2">
                            <div className="loading loading-spinner"></div>
                            <div>loading {itemName}</div>
                        </div>
                    ) : (
                        <>
                            <div className="border-b border-base-300 sticky top-0 bg-base-100 z-10">
                                <div className="relative w-full mb-2">
                                    <input
                                        type="text"
                                        value={searchInput}
                                        onChange={(e) =>
                                            setSearchInput(e.target.value)
                                        }
                                        placeholder="Search..."
                                        className="input input-sm w-full pr-8"
                                    />
                                    {searchInput && (
                                        <button
                                            onMouseDown={(e) => {
                                                e.preventDefault();
                                                setSearchInput("");
                                            }}
                                            className="absolute right-2 z-100 top-1/2 -translate-y-1/2 text-base-content/70 hover:text-base-content"
                                        >
                                            <FaTimes />
                                        </button>
                                    )}
                                </div>

                                <button
                                    onClick={handleClearSelectionClick}
                                    disabled={!isClearSelectionEnabled}
                                    className="sticky z-10 top-0 w-full text-left px-2 py-1 rounded-lg hover:bg-primary/10 disabled:hover:bg-transparent disabled:cursor-default cursor-pointer disabled:opacity-50"
                                >
                                    Clear selection
                                </button>
                            </div>

                            <div className="overflow-y-auto flex flex-col h-full">
                                {filteredOptions.length === 0 ? (
                                    <div className="text-sm text-gray-500 px-2 py-1">
                                        No matches found
                                    </div>
                                ) : (
                                    filteredOptions.map((option) => (
                                        <label
                                            key={option.value}
                                            className="flex items-center whitespace-nowrap cursor-pointer px-2 py-1 hover:bg-primary/10 rounded"
                                        >
                                            <input
                                                type={
                                                    singleSelect ||
                                                    EXCLUSIVE_OPTION_VALUES.includes(
                                                        option.value
                                                    )
                                                        ? "radio"
                                                        : "checkbox"
                                                }
                                                name={formFieldName}
                                                value={option.value}
                                                checked={selectedOptions.includes(
                                                    option.value
                                                )}
                                                onChange={handleChange}
                                                className={clsx(
                                                    singleSelect
                                                        ? "radio radio-primary cursor-pointer"
                                                        : "checkbox checkbox-sm checkbox-primary cursor-pointer"
                                                )}
                                            />
                                            <span className="ml-2">
                                                {highlightMatch(option)}
                                            </span>
                                        </label>
                                    ))
                                )}
                            </div>
                        </>
                    )}
                </ul>
            </div>
        );
    }
);

export default MultiSelectSearchableDropdown;
