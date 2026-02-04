import { Draggable } from "@/Components/DnD/Draggable";
import { Droppable } from "@/Components/DnD/Droppable";
import { DndContext, DragOverlay } from "@dnd-kit/core";
import clsx from "clsx";
import React, { useState } from "react";

function closenessRatio(value, reference, maxDistance = 50) {
	const distance = Math.abs(value - reference);
	if (distance >= maxDistance) return 0;
	return 1 - distance / maxDistance;
}

function lerp(a, b, t) {
	return Math.round(a + (b - a) * t);
}

const draggable = ({ d, updateDraggable }) => {
	return (
		<div className="z-50 w-full items-center bg-base-200 h-full justify-center flex rounded-lg text-sm">
			<div className="w-30 text-xs pl-1 rounded opacity-75">{d.name}</div>
			<input
				type="number"
				className="w-full rounded"
				value={d.value}
				onChange={(e) => updateDraggable(d.id, "value", e.target.value)}
			/>
		</div>
	);
};

function generateRandomContainers(count = 20) {
	const result = new Map();

	const randomInt = (min, max) =>
		Math.floor(Math.random() * (max - min + 1)) + min;

	while (result.size < count) {
		const x = randomInt(3, 9);
		const y = randomInt(2, 9);
		const key = `${x}x${y}`;

		// Avoid duplicate keys
		if (result.has(key)) continue;

		const baseWip = randomInt(100_000, 800_000);
		const stepWip = randomInt(30_000, 120_000);

		const wip = {
			f1: baseWip,
			f2: baseWip + stepWip,
			f3: baseWip + stepWip * 2,
		};

		const baseLot = baseWip + randomInt(50_000, 300_000);
		const stepLot = randomInt(30_000, 120_000);

		const lot = {
			f1: baseLot,
			f2: baseLot + stepLot,
			f3: baseLot + stepLot * 2,
		};

		result.set(key, {
			name: key,
			wip,
			lot,
		});
	}

	return result;
}

const containers = generateRandomContainers(20);

const dropHere = () => {
	return null;
	// <div className="opacity-25 flex flex-1 items-center justify-center rounded border border-dashed text-sm h-full">
	// 	Drop here
	// </div>
};

const initialDraggables = new Map([
	["draggable1", { id: "draggable1", name: "HVIS2049VKIFPPDK", value: 150000 }],
	["draggable2", { id: "draggable2", name: "Item 2", value: 250000 }],
	["draggable3", { id: "draggable3", name: "Item 3", value: 100000 }],
	["draggable4", { id: "draggable4", name: "Item 4", value: 350000 }],
	["draggable5", { id: "draggable5", name: "Item 5", value: 500000 }],
	["draggable6", { id: "draggable6", name: "Item 6", value: 600000 }],
	["draggable7", { id: "draggable7", name: "Item 7", value: 200000 }],
	["draggable8", { id: "draggable8", name: "Item 8", value: 300000 }],
	["draggable9", { id: "draggable9", name: "Item 9", value: 450000 }],
	["draggable10", { id: "draggable10", name: "Item 10", value: 120000 }],
	["draggable11", { id: "draggable11", name: "Item 11", value: 280000 }],
	["draggable12", { id: "draggable12", name: "Item 12", value: 550000 }],
	["draggable13", { id: "draggable13", name: "Item 13", value: 320000 }],
	["draggable14", { id: "draggable14", name: "Item 14", value: 480000 }],
	["draggable15", { id: "draggable15", name: "Item 15", value: 110000 }],
	["draggable16", { id: "draggable16", name: "Item 16", value: 750000 }],
	["draggable17", { id: "draggable17", name: "Item 17", value: 180000 }],
	["draggable18", { id: "draggable18", name: "Item 18", value: 400000 }],
	["draggable19", { id: "draggable19", name: "Item 19", value: 130000 }],
	["draggable20", { id: "draggable20", name: "Item 20", value: 220000 }],
	["draggable21", { id: "draggable21", name: "Item 21", value: 380000 }],
	["draggable22", { id: "draggable22", name: "Item 22", value: 140000 }],
	["draggable23", { id: "draggable23", name: "Item 23", value: 340000 }],
	["draggable24", { id: "draggable24", name: "Item 24", value: 460000 }],
	["draggable25", { id: "draggable25", name: "Item 25", value: 580000 }],
	["draggable26", { id: "draggable26", name: "Item 26", value: 260000 }],
	["draggable27", { id: "draggable27", name: "Item 27", value: 420000 }],
	["draggable28", { id: "draggable28", name: "Item 28", value: 170000 }],
	["draggable29", { id: "draggable29", name: "Item 29", value: 650000 }],
]);

function PackageBodySizeCapacityList() {
	const target = 100;

	const [activeDraggableID, setActiveDraggableID] = useState(null);

	const innerDroppables = {
		f1: { name: "F1", color: "var(--color-f1color)" },
		f2: { name: "F2", color: "var(--color-f2color)" },
		f3: { name: "F3", color: "var(--color-f3color)" },
	};

	// draggables stored as a Map
	const [draggables, setDraggables] = useState(initialDraggables);

	const activeDraggableObject = activeDraggableID
		? draggables.get(activeDraggableID)
		: null;

	// locations: keep the same structure
	const [locations, setLocations] = useState({});

	React.useEffect(() => {
		const containerKeys = Array.from(containers.keys());
		const innerKeys = Object.keys(innerDroppables);

		setLocations(
			Object.fromEntries(
				Array.from(draggables.keys()).map((id) => {
					const randomContainer =
						containerKeys[Math.floor(Math.random() * containerKeys.length)];
					const randomInner =
						innerKeys[Math.floor(Math.random() * innerKeys.length)];
					return [id, `${randomContainer}-${randomInner}`];
				}),
			),
		);
	}, [draggables]);

	function handleDragStart(event) {
		setActiveDraggableID(event.active.id);
	}

	function handleDragEnd(event) {
		const { active, over } = event;

		setLocations((prev) => ({
			...prev,
			[active.id]: over ? (over.id === "unassigned" ? null : over.id) : null,
		}));

		setActiveDraggableID(null);
	}

	function updateDraggable(id, key, newValue) {
		setDraggables((prev) => {
			const next = new Map(prev);
			const item = next.get(id);
			if (!item) return next;
			next.set(id, {
				...item,
				[key]: key === "value" ? parseInt(newValue) || 0 : newValue,
			});
			return next;
		});
	}

	const getDraggablesInOuter = (outerId) => {
		const innerIds = Object.keys(innerDroppables).map(
			(inner) => `${outerId}-${inner}`,
		);
		return Array.from(draggables.values()).filter((d) =>
			innerIds.includes(locations[d.id]),
		);
	};

	const unassignedCount = Array.from(draggables.values()).filter(
		(d) => locations[d.id] === null,
	).length;

	return (
		<div className="relative">
			<div className="p-2 flex justify-between bg-base-200 shadow-lg">
				<h1>LFCSP</h1>
				<button type="button" className="btn btn-primary">
					Save for Today
				</button>
			</div>

			<DndContext
				// collisionDetection={closestCenter}
				onDragStart={handleDragStart}
				onDragEnd={handleDragEnd}
			>
				<div className="grid grid-cols-1 h-full md:grid-cols-2 gap-1 w-full">
					{Array.from(containers.entries()).map(([outerId, outerData]) => {
						console.log(
							"🚀 ~ PackageBodySizeCapacityList ~ outerData:",
							outerData,
						);
						console.log("🚀 ~ PackageBodySizeCapacityList ~ outerId:", outerId);

						const draggablesInOuter = getDraggablesInOuter(outerId);
						const wip = outerData.wip;
						const lot = outerData.lot;

						const totalValue = draggablesInOuter.reduce(
							(sum, d) => sum + d.value,
							0,
						);

						return (
							<div
								key={outerId}
								className="relative flex flex-col flex-1/2 rounded-lg border border-base-content/20 shadow-sm"
							>
								<div className="flex sticky shadow-lg z-10 -top-8 bg-base-200 px-2 pt-2 items-center justify-between mb-4">
									<h2 className="text-lg font-semibold">{outerId}</h2>
									<div className="text-sm text-base-content">
										Count: {draggablesInOuter.length} • Total: {totalValue}
									</div>
								</div>

								<div className="flex h-full">
									{Object.entries(innerDroppables).map(
										([innerId, innerData]) => {
											const combinedId = `${outerId}-${innerId}`;
											console.log(
												"🚀 ~ PackageBodySizeCapacityList ~ combinedId:",
												combinedId,
											);
											const totalWip = wip[innerId] || 0;

											console.log(
												"🚀 ~ PackageBodySizeCapacityList ~ totalWip:",
												totalWip,
											);
											const totalLot = lot[innerId] || 0;

											console.log(
												"🚀 ~ PackageBodySizeCapacityList ~ locations:",
												locations,
											);
											const innerDraggables = Array.from(
												draggables.values(),
											).filter((d) => {
												return locations[d.id] === combinedId;
											});

											console.log(
												"🚀 ~ PackageBodySizeCapacityList ~ innerDraggables:",
												innerDraggables,
											);

											const isEmpty =
												!activeDraggableID && innerDraggables.length === 0;

											const isActiveDraggableInside = activeDraggableObject
												? locations[activeDraggableID] === combinedId
												: false;

											console.log(
												"🚀 ~ PackageBodySizeCapacityList ~ isActiveDraggableInside:",
												isActiveDraggableInside,
											);

											console.log(
												"🚀 ~ PackageBodySizeCapacityList ~ innerDraggables:",
												innerDraggables,
											);

											const totalInnerValue = innerDraggables.reduce(
												(sum, d) => sum + d.value,
												0,
											);

											const potentialCapacityAddedActiveDraggable =
												totalInnerValue +
												(activeDraggableObject
													? activeDraggableObject.value
													: 0) *
													(isActiveDraggableInside ? -1 : 1);

											const utilPercentage =
												totalInnerValue > 0
													? ((totalWip / totalInnerValue) * 100).toFixed(2)
													: "0.00";

											const potentialUtilPercentage =
												potentialCapacityAddedActiveDraggable > 0
													? (
															(totalWip /
																potentialCapacityAddedActiveDraggable) *
															100
														).toFixed(2)
													: 0;

											const ratio = closenessRatio(
												potentialUtilPercentage,
												target,
											);

											const defaultColor = [255, 0, 0];
											const green = [0, 255, 0];

											const textColor = {
												color: `rgb(
                      ${lerp(defaultColor[0], green[0], ratio)},
                      ${lerp(defaultColor[1], green[1], ratio)},
                      ${lerp(defaultColor[2], green[2], ratio)}
                    )`,
											};

											return (
												<Droppable
													data={{ color: innerData.color, className: "flex-1" }}
													key={combinedId}
													id={combinedId}
												>
													<div
														className={clsx(
															"flex flex-col flex-1 h-full w-full",
														)}
													>
														<div className="sticky bg-base-200 z-10 top-0 p-1 text-sm border-b border-base-content/10">
															<div
																className="font-medium w-full flex justify-between"
																style={{ color: innerData.color }}
															>
																<div className="text-xs">
																	{innerData.name} WIP
																</div>
																<span className="font-mono">
																	{Number(totalWip).toLocaleString()}
																</span>
															</div>
															<div
																className={clsx("w-full flex justify-between", {
																	"opacity-50": isEmpty,
																})}
															>
																<div className="text-xs">Capacity</div>
																<span className="font-mono">
																	{isEmpty ? (
																		"-"
																	) : (
																		<div>
																			{activeDraggableID && (
																				<span className="line-through text-xs opacity-75 mr-1">
																					{Number(
																						totalInnerValue,
																					).toLocaleString()}
																				</span>
																			)}

																			{Number(
																				potentialCapacityAddedActiveDraggable,
																			).toLocaleString()}
																		</div>
																	)}
																</span>
															</div>
															<div
																className={clsx("w-full flex justify-between", {
																	"opacity-50": isEmpty,
																})}
															>
																<div className="text-xs">Util</div>
																<span className="text-lg font-extrabold">
																	{isEmpty ? (
																		"-"
																	) : (
																		<div>
																			{activeDraggableID && (
																				<span className="line-through text-xs opacity-75 mr-1">
																					{`${utilPercentage}%`}
																				</span>
																			)}
																			<span
																				style={isEmpty ? {} : textColor}
																			>{`${potentialUtilPercentage}%`}</span>
																		</div>
																	)}
																</span>
															</div>
														</div>

														<div className="flex h-full flex-col flex-1 p-1">
															{innerDraggables.length > 0
																? innerDraggables.map((d) => (
																		<Draggable
																			key={d.id}
																			id={d.id}
																			containerClassName={clsx(
																				"h-8 justify-between",
																				{
																					"opacity-0":
																						activeDraggableID === d.id,
																				},
																			)}
																		>
																			{draggable({ d, updateDraggable })}
																		</Draggable>
																	))
																: dropHere()}
														</div>
													</div>
												</Droppable>
											);
										},
									)}
								</div>
							</div>
						);
					})}
				</div>

				<DragOverlay
					adjustScale={false}
					transition="transform 150ms cubic-bezier(0.2, 0, 0, 1)"
				>
					{activeDraggableID ? (
						<div
							className={clsx(
								"h-8 justify-between ring-2 rounded-lg ring-accent",
							)}
						>
							{draggable({
								d: draggables.get(activeDraggableID),
								updateDraggable,
							})}
						</div>
					) : null}
				</DragOverlay>

				{/* Unassigned droppable */}
				<Droppable
					id="unassigned"
					data={{ className: "mt-4 z-40 flex flex-1" }}
				>
					<div className="rounded-lg border border-base-content/20 min-h-20 bg-base-200 flex-1">
						<div className="mb-2 text-sm font-medium text-base-content">
							{unassignedCount} Unassigned
						</div>
						<div className="flex gap-3 flex-wrap">
							{Array.from(draggables.values())
								.filter((d) => locations[d.id] === null)
								.map((d) => (
									<Draggable
										key={d.id}
										id={d.id}
										containerClassName={clsx(
											"bg-base-300 w-32 h-8 justify-between border-base-content/20",
											{
												"opacity-0": activeDraggableID === d.id,
											},
										)}
									>
										{draggable({ d, updateDraggable })}
									</Draggable>
								))}
							{Array.from(draggables.values()).filter(
								(d) => locations[d.id] === null,
							).length === 0 && dropHere()}
						</div>
					</div>
				</Droppable>
			</DndContext>
		</div>
	);
}

export default PackageBodySizeCapacityList;
