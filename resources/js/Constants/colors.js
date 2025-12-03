export const LIGHT_THEME_NAME = "lofi";
export const DARK_THEME_NAME = "lofi-dark";

// TODO it's not color anymore, this is a config
export const FACTORIES_COLOR = [
    { key: "f1", 
        colorVar: { 
            quantity: "var(--color-f1color)", 
            lots: "var(--color-f1color-15)", 
            out: "var(--color-f1color-30)", 
            capacity: "var(--color-accent)"
        },
        strokeWidth: 1,
        className: "opacity-100",
    },
    { key: "f2", 
        colorVar: { 
            quantity: "var(--color-f2color)", 
            lots: "var(--color-f2color-15)", 
            out: "var(--color-f2color-30)", 
            capacity: "var(--color-accent)"
        },
        strokeWidth: 1,
        className: "opacity-100",
    },
    { key: "f3", 
        colorVar: { 
            quantity: "var(--color-f3color)", 
            lots: "var(--color-f3color-15)", 
            out: "var(--color-f3color-30)", 
            capacity: "var(--color-accent)"
        },
        strokeWidth: 1,
        className: "opacity-100",
    },
    { key: "overall", 
        colorVar: { 
            quantity: "var(--color-base-content)", 
            lots: "var(--color-f2color)", 
            out: "var(--color-f3color)", 
            capacity: "var(--color-accent)"
        },
        strokeWidth: 2,
        className: "opacity-100",
    },
];