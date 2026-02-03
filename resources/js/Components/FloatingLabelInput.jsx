import React from "react";
import clsx from "clsx";
import { useState, useEffect, useMemo } from "react";

export default function FloatingLabelInput({
	id,
	label,
	type = "text",
	value,
	onChange,
	helperText,
	errorText,
	successText,
	variant = "outlined", // 'outlined' | 'filled' | 'standard'
	color = "primary",
	className = "",
	labelClassName = "",
	alwaysFloatLabel = false,
	...props
}) {
	const [hasValue, setHasValue] = useState(Boolean(value));

	const focusColor = `focus:border-${color}`;
	const peerFocusColor = `peer-focus:text-${color}`;

	const isPickerType = useMemo(
		() =>
			[
				"date",
				"color",
				"file",
				"image",
				"radio",
				"datetime-local",
				"month",
				"time",
				"week",
			].includes(type),
		[type],
	);

	useEffect(() => {
		setHasValue(Boolean(value));
	}, [value]);

	const baseInputClass = clsx(
		"block text-sm disabled:cursor-not-allowed disabled:opacity-50 appearance-none focus:outline-none focus:ring-0 peer transition-colors duration-200",
		focusColor,
		{
			outlined:
				variant === "outlined" &&
				clsx(
					"px-2.5 pb-2.5 pt-4 rounded-lg border border-base-content/10 bg-transparent",
				),
			filled:
				variant === "filled" &&
				clsx(
					"rounded-t-lg px-2.5 pb-2.5 pt-5 bg-base-content/10 border-0 border-b-2 border-base-content/10",
				),
			standard:
				variant === "standard" &&
				clsx("py-2.5 px-0 border-0 border-b-2 border-base-content/10"),
		}[variant],
		className,
	);

	const labelBaseClass =
		" top-0 pointer-events-none truncate leading-none text-base-content/75 absolute text-sm duration-300 origin-[0] transform transition-all";

	const variantClasses = {
		outlined: [
			"px-2 mt-0.5 left-2",
			hasValue || isPickerType || alwaysFloatLabel
				? "scale-75 top-0 pb-4 -translate-y-1/2"
				: "top-1/2 -translate-y-1/2",
			"peer-focus:scale-75 peer-focus:pb-4 peer-focus:top-0 peer-focus:-translate-y-1/2",
		],
		filled: [
			"top-4 left-2.5",
			hasValue || isPickerType || alwaysFloatLabel
				? "scale-75 -translate-y-4"
				: "scale-100 translate-y-0",
			`peer-focus:scale-75 peer-focus:-translate-y-4`,
		],
		standard: [
			"top-3 left-0",
			hasValue || isPickerType || alwaysFloatLabel
				? "scale-75 -translate-y-6"
				: "scale-100 translate-y-0",
			`peer-focus:scale-75 peer-focus:-translate-y-6`,
		],
	};

	const labelClass = clsx(
		peerFocusColor,
		labelBaseClass,
		variantClasses[variant],
		labelClassName,
	);

	const messageClass = (color) =>
		clsx("mt-2 text-xs", {
			"text-base-content ": color === "helper",
			"text-error ": color === "error",
			"text-success ": color === "success",
		});

	return (
		<div>
			<div className="relative">
				<input
					id={id}
					type={type}
					value={value}
					onChange={(e) => {
						setHasValue(Boolean(e.target.value));
						onChange?.(e);
					}}
					placeholder=" "
					className={baseInputClass}
					{...props}
				/>
				<label htmlFor={id} className={labelClass}>
					{label}
				</label>
			</div>

			{helperText && !errorText && !successText && (
				<p className={messageClass("helper")}>{helperText}</p>
			)}
			{errorText && <p className={messageClass("error")}>{errorText}</p>}
			{successText && (
				<p className={messageClass("success")}>
					<span className="font-medium">Success: </span>
					{successText}
				</p>
			)}
		</div>
	);
}
