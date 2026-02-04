import { closestCenter, DndContext, DragOverlay } from "@dnd-kit/core";
import clsx from "clsx";
import { useState } from "react";
import { Draggable } from "@/Components/DnD/Draggable";
import { Droppable } from "@/Components/DnD/Droppable";

function closenessRatio(value, reference, maxDistance = 50) {
	const distance = Math.abs(value - reference);
	if (distance >= maxDistance) return 0;
	return 1 - distance / maxDistance;
}

const draggable = ({ d, updateDraggable }) => {
	return (
		<div className="z-50 bg-base-300 w-full px-2 h-full justify-center flex flex-col rounded text-sm">
			<div className="w-full rounded px-1 opacity-75">{d.name}</div>
			<input
				type="number"
				className="w-full rounded px-1"
				value={d.value}
				onChange={(e) => updateDraggable(d.id, "value", e.target.value)}
			/>
		</div>
	);
};

const dropHere = () => {
	return (
		<div className="opacity-50 flex flex-1 items-center justify-center rounded border border-dashed text-sm min-h-14 h-full">
			Drop here
		</div>
	);
};

function PackageBodySizeCapacityList() {
	const target = 100;
	const containers = ["A", "B", "C"];
	const [activeId, setActiveId] = useState(null);
	const innerDroppables = {
		F1: { name: "F1", wip: 0, color: "var(--color-f1color)" },
		F2: { name: "F2", wip: 10, color: "var(--color-f2color)" },
		F3: { name: "F3", wip: 11110, color: "var(--color-f3color)" },
	};

	const [draggables, setDraggables] = useState([
		{ id: "draggable1", name: "Item 1", value: 10 },
		{ id: "draggable2", name: "Item 2", value: 20 },
		{ id: "draggable3", name: "Item 3", value: 5 },
	]);

	const [locations, setLocations] = useState({
		draggable1: null,
		draggable2: null,
		draggable3: null,
	});

	function handleDragEnd(event) {
		const { active, over } = event;

		setLocations((prev) => ({
			...prev,
			[active.id]: over ? over.id : null,
		}));

		setActiveId(null);
	}

	function handleDragStart(event) {
		setActiveId(event.active.id);
	}

	function updateDraggable(id, key, newValue) {
		setDraggables((prev) =>
			prev.map((d) =>
				d.id === id
					? {
							...d,
							[key]: key === "value" ? parseInt(newValue) || 0 : newValue,
						}
					: d,
			),
		);
	}

	const getDraggablesInOuter = (outerId) => {
		const innerIds = Object.keys(innerDroppables).map(
			(inner) => `${outerId}-${inner}`,
		);
		return draggables.filter((d) => innerIds.includes(locations[d.id]));
	};

	return (
		<DndContext
			collisionDetection={closestCenter}
			onDragStart={handleDragStart}
			onDragEnd={handleDragEnd}
		>
			<div className="space-y-6">
				{containers.map((outerId) => {
					const draggablesInOuter = getDraggablesInOuter(outerId);
					const totalValue = draggablesInOuter.reduce(
						(sum, d) => sum + d.value,
						0,
					);

					return (
						<div
							key={outerId}
							className="rounded-lg border border-base-content/20 shadow-sm"
						>
							<div className="flex px-2 pt-2 items-center justify-between mb-4">
								<h2 className="text-lg font-semibold">Outer {outerId}</h2>
								<div className="text-sm text-base-content">
									Count: {draggablesInOuter.length} • Total: {totalValue}
								</div>
							</div>

							<div className="flex gap-3">
								{Object.entries(innerDroppables).map(([innerId, innerData]) => {
									const combinedId = `${outerId}-${innerId}`;
									const innerDraggables = draggables.filter(
										(d) => locations[d.id] === combinedId,
									);

									console.log(
										"🚀 ~ PackageBodySizeCapacityList ~ innerDraggables:",
										innerDraggables,
									);
									const totalInnerValue = innerDraggables.reduce(
										(sum, d) => sum + d.value,
										0,
									);
									console.log(
										"🚀 ~ PackageBodySizeCapacityList ~ totalInnerValue:",
										totalInnerValue,
									);

									const utilPercentage =
										totalInnerValue > 0
											? (innerData.wip / totalInnerValue) * 100
											: 0;

									const ratio = closenessRatio(utilPercentage, target);
									console.log(
										"🚀 ~ PackageBodySizeCapacityList ~ ratio:",
										ratio,
									);
									const textColor = `rgba(255, 0, 0, ${ratio})`;

									return (
										<Droppable
											data={{ color: innerData.color, className: "flex-1" }}
											key={combinedId}
											id={combinedId}
										>
											<div className={clsx("flex flex-col flex-1 w-full")}>
												<div className="p-2 text-sm border-b border-base-content/10">
													<div
														className="font-medium w-full flex justify-between"
														style={{ color: innerData.color }}
													>
														{innerData.name} WIP{" "}
														<span className="font-mono">
															{Number(innerData.wip).toLocaleString()}
														</span>
													</div>
													<div className="w-full flex justify-between">
														<div>Capacity</div>
														<span className="font-mono">
															{Number(totalInnerValue).toLocaleString()}
														</span>
													</div>
													<div className="w-full flex justify-between">
														<div>Util</div>
														<span
															className="font-mono"
															style={{ color: textColor }}
														>
															{totalInnerValue > 0
																? (
																		(innerData.wip / totalInnerValue) *
																		100
																	).toFixed(2)
																: 0}
															%
														</span>
													</div>
												</div>

												<div className="flex h-full flex-col flex-1 gap-1 p-1">
													{innerDraggables.length > 0
														? innerDraggables.map((d) => (
																<Draggable
																	key={d.id}
																	id={d.id}
																	containerClassName="h-14 justify-between border-base-content/20"
																>
																	{draggable({ d, updateDraggable })}
																</Draggable>
															))
														: dropHere()}
												</div>
											</div>
										</Droppable>
									);
								})}
							</div>
						</div>
					);
				})}

				<div className="rounded-lg border bg-base-200 p-4">
					<div className="mb-2 text-sm font-medium text-base-content">
						Unassigned
					</div>
					<div className="flex gap-3">
						{draggables
							.filter((d) => locations[d.id] === null)
							.map((d) => (
								<Draggable
									key={d.id}
									id={d.id}
									containerClassName="bg-base-300 h-14 justify-between border-base-content/20"
								>
									{draggable({ d, updateDraggable })}
								</Draggable>
							))}
					</div>
				</div>
			</div>

			<DragOverlay>
				{activeId ? (
					<div className="h-14 justify-between border-base-content/20">
						{draggable({
							d: draggables.find((d) => d.id === activeId),
							updateDraggable,
						})}
					</div>
				) : null}
			</DragOverlay>
		</DndContext>
	);
}

export default PackageBodySizeCapacityList;
