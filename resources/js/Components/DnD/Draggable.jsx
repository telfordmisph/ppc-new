import { useDraggable } from "@dnd-kit/core";
import clsx from "clsx";

function Draggable(props) {
	const { attributes, listeners, setNodeRef, transform } = useDraggable({
		id: props.id,
	});
	console.log("🚀 ~ Draggable ~ props:", props);
	const style = transform
		? {
				transform: `translate3d(${transform.x}px, ${transform.y}px, 0)`,
			}
		: undefined;

	return (
		<div
			ref={setNodeRef}
			style={style}
			className={clsx(
				"relative flex items-center border border-base-100 rounded-lg",
				props.containerClassName,
			)}
		>
			<div className="flex-1">{props.children}</div>

			<div
				{...listeners}
				{...attributes}
				className="cursor-grab opacity-75 select-none h-full flex items-center px-2 hover:bg-secondary/50"
			>
				⠿
			</div>
		</div>
	);
}
export { Draggable };
