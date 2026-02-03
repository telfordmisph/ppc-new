import React from "react";
import { createPortal } from "react-dom";

// TODO: change name dropdownportal to contaierpota

const ContainerPortal = React.memo(
	React.forwardRef(function ContainerPortal(
		{ children, parentOpen, onClose },
		ref,
	) {
		const childrenRef = React.useRef(null);

		React.useEffect(() => {
			if (!parentOpen) return;

			function handleOutside(e) {
				if (
					ref?.current?.contains(e.target) ||
					childrenRef.current?.contains(e.target)
				)
					return;

				onClose?.();
			}

			document.addEventListener("mousedown", handleOutside);
			return () => document.removeEventListener("mousedown", handleOutside);
		}, [parentOpen, ref, onClose]);

		React.useEffect(() => {
			if (!parentOpen) return;

			function handleScroll() {
				onClose?.();
			}

			window.addEventListener("scroll", handleScroll, true);
			return () => window.removeEventListener("scroll", handleScroll, true);
		}, [parentOpen, onClose]);

		const rect = ref?.current?.getBoundingClientRect();
		if (!parentOpen || !rect) return null;

		return createPortal(
			<div
				className="fixed z-50"
				ref={childrenRef}
				style={{
					top: rect.bottom,
					left: rect.left,
				}}
			>
				{children}
			</div>,
			document.body,
		);
	}),
);

export default ContainerPortal;
