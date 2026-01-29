export const LIGHT_THEME_NAME = "lofi";
export const DARK_THEME_NAME = "lofi-dark";

// TODO it's not color anymore, this is a config
export const WIP_OUT_CAPACITY = [
	{
		key: "f1",
		colorVar: {
			wip: "var(--color-primary)",
			out: "var(--color-secondary)",
			capacity: "var(--color-accent)",
		},
		strokeWidth: 1,
		r: 3,
		className: "opacity-100",
	},
	{
		key: "f2",
		colorVar: {
			wip: "var(--color-primary)",
			out: "var(--color-secondary)",
			capacity: "var(--color-accent)",
		},
		strokeWidth: 1,
		r: 3,
		className: "opacity-100",
	},
	{
		key: "f3",
		colorVar: {
			wip: "var(--color-primary)",
			out: "var(--color-secondary)",
			capacity: "var(--color-accent)",
		},
		strokeWidth: 1,
		r: 3,
		className: "opacity-100",
	},
	{
		key: "overall",
		colorVar: {
			wip: "var(--color-primary)",
			out: "var(--color-secondary)",
			capacity: "var(--color-accent)",
		},
		strokeWidth: 1,
		r: 3,
		className: "opacity-100",
	},
];

export const FACTORY_COLORS = [
	{
		key: "f1",
		colorVar: {
			wip: "var(--color-f1color)",
			out: "var(--color-f1color)",
			lots: "var(--color-f1color-15)",
		},
		strokeWidth: 1,
		r: 3,
		className: "opacity-100",
	},
	{
		key: "f2",
		colorVar: {
			wip: "var(--color-f2color)",
			out: "var(--color-f2color)",
			lots: "var(--color-f2color-15)",
		},
		strokeWidth: 1,
		r: 3,
		className: "opacity-100",
	},
	{
		key: "f3",
		colorVar: {
			wip: "var(--color-f3color)",
			out: "var(--color-f3color)",
			lots: "var(--color-f3color-15)",
		},
		strokeWidth: 1,
		r: 3,
		className: "opacity-100",
	},
	{
		key: "overall",
		colorVar: {
			wip: "var(--color-accent)",
			out: "var(--color-accent)",
			lots: "var(--color-f2color)",
		},
		strokeWidth: 2,
		r: 3,
		className: "opacity-100",
	},
];
