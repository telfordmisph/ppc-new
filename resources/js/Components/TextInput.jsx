import { forwardRef, useEffect, useImperativeHandle, useRef } from "react";
import { DARK_THEME_NAME } from "@/Constants/colors";
import { useThemeStore } from "@/Store/themeStore";

export default forwardRef(function TextInput(
	{ type = "text", className = "", isFocused = false, ...props },
	ref,
) {
	const { theme } = useThemeStore();

	const localRef = useRef(null);

	useImperativeHandle(ref, () => ({
		focus: () => localRef.current?.focus(),
	}));

	useEffect(() => {
		if (isFocused) {
			localRef.current?.focus();
		}
	}, [isFocused]);

	const themeColor = theme === DARK_THEME_NAME ? "bg-gray-800" : "bg-white";

	return (
		<input
			{...props}
			type={type}
			className={
				themeColor +
				" rounded-lg first-letter:rounded-lg border-gray-300 shadow-lg focus:border-indigo-500 focus:ring-indigo-500 " +
				className
			}
			ref={localRef}
		/>
	);
});
