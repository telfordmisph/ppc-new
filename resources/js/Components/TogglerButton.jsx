import React from "react";
import { FaCheck } from "react-icons/fa6";
import { FaTimes } from "react-icons/fa";

const TogglerButton = ({
    toggleButtons,
    visibleBars,
    toggleBar,
    toggleAll,
}) => {
    return (
        <div className="join rounded-lg">
            {toggleButtons.map(({ key, label, activeClass, inactiveClass }) => (
                <button
                    key={key}
                    onClick={() => toggleBar(key)}
                    className={`join-item flex btn btn-sm text-sm items-center gap-x-2 px-3 py-1 transition-colors duration-200 ${
                        visibleBars[key] ? activeClass : inactiveClass
                    }`}
                >
                    {label}
                    {visibleBars[key] ? <FaCheck /> : <FaTimes />}
                </button>
            ))}

            {toggleAll && (
                <button
                    onClick={toggleAll}
                    className="join-item px-3 py-1 btn btn-sm text-sm btn-outline"
                >
                    Toggle All
                </button>
            )}
        </div>
    );
};

export default TogglerButton;
