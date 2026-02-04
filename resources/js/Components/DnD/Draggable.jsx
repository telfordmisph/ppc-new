import { useDraggable } from "@dnd-kit/core";
import clsx from "clsx";

function Draggable(props) {
	const { attributes, listeners, setNodeRef, transform } = useDraggable({
		id: props.id,
	});
	const style = transform
		? {
				transform: `translate3d(${transform.x}px, ${transform.y}px, 0)`,
			}
		: undefined;

	return (
		<div
			className={clsx(
				"flex border shadow-xl border-base-100 rounded-lg",
				props.containerClassName,
			)}
			ref={setNodeRef}
			style={style}
		>
			<div>{props.children}</div>
			<div
				{...listeners}
				{...attributes}
				className="cursor-grab opacity-75 select-none h-full rounded-lg mb-1 text-base-content flex items-center px-2 hover:bg-secondary/50"
			>
				⠿
			</div>
		</div>
	);
}
export { Draggable };
