import React from "react";
import clsx from "clsx";

const Tabs = ({
    options,
    selectedFactory,
    handleFactoryChange,
    tabClassName,
}) => {
    return (
        <div
            role="tablist"
            className={clsx("tabs border-primary rounded-b-box", tabClassName)}
        >
            {options.map((factory) => (
                <a
                    key={factory}
                    role="tab"
                    className={clsx(
                        "tab",
                        selectedFactory === factory &&
                            "tab-active text-primary font-extrabold border-b-2 border-primary rounded-t-lg bg-linear-to-t from-primary/35 to-transparent to-50%"
                    )}
                    onClick={() => handleFactoryChange(factory)}
                >
                    {factory}
                </a>
            ))}
        </div>
    );
};

export default Tabs;
