import { useDroppable } from "@dnd-kit/core";
import clsx from "clsx";

function Droppable({ id, children, data }) {
	const { isOver, setNodeRef } = useDroppable({ id });

	return (
		<div
			ref={setNodeRef}
			style={data?.color ? { "--ring-color": data.color } : {}}
			className={clsx(
				"rounded-md transition-all",
				data.className,
				isOver && [
					"ring-2",
					"ring-(--ring-color)",
					"ring-offset-2",
					"ring-offset-base-100",
				],
			)}
		>
			{children}
		</div>
	);
}

export { Droppable };
