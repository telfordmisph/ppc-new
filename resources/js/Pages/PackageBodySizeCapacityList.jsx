import { Draggable } from "@/Components/DnD/Draggable";
import { Droppable } from "@/Components/DnD/Droppable";
import { MachineDraggable } from "@/Components/DnD/MachineDraggable";
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";
import Tabs from "@/Components/Tabs";
import { useFetch } from "@/Hooks/useFetch";
import { DndContext, DragOverlay } from "@dnd-kit/core";
import { router } from "@inertiajs/react";
import clsx from "clsx";
import React, { useEffect, useState } from "react";

function closenessRatio(value, minReference, maxReference, maxDistance = 50) {
	if (value >= minReference && value <= maxReference) {
		return 1;
	}

	const distance =
		value < minReference ? minReference - value : value - maxReference;

	if (distance >= maxDistance) return 0;
	return 1 - distance / maxDistance;
}

function lerp(a, b, t) {
	return Math.round(a + (b - a) * t);
}

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

const containers = generateRandomContainers(10);

const dropHere = () => {
	return null;
	// <div className="opacity-25 flex flex-1 items-center justify-center rounded border border-dashed text-sm h-full">
	// 	Drop here
	// </div>
	return null;
	// <div className="opacity-25 flex flex-1 items-center justify-center rounded border border-dashed text-sm h-full">
	// 	Drop here
	// </div>
};

const initialDraggables = new Map([
	[
		"draggable1",
		{ id: "draggable1", machineId: 1, name: "HVIS2049VKIFPPDK", value: 150000 },
	],
	[
		"draggable2",
		{ id: "draggable2", machineId: 1, name: "HVIS2049VKIFPPDK", value: 250000 },
	],
	[
		"draggable3",
		{ id: "draggable3", machineId: 3, name: "ba 3", value: 100000 },
	],
	[
		"draggable4",
		{ id: "draggable4", machineId: 4, name: "testing", value: 350000 },
	],
	[
		"draggable5",
		{ id: "draggable5", machineId: 5, name: "Item 5", value: 500000 },
	],
	[
		"draggable6",
		{ id: "draggable6", machineId: 6, name: "Item 6", value: 600000 },
	],
	[
		"draggable7",
		{ id: "draggable7", machineId: 7, name: "Item 7", value: 200000 },
	],
	[
		"draggable8",
		{ id: "draggable8", machineId: 8, name: "Item 8", value: 300000 },
	],
	[
		"draggable9",
		{ id: "draggable9", machineId: 9, name: "Item 9", value: 450000 },
	],
	[
		"draggable10",
		{ id: "draggable10", machineId: 10, name: "Item 10", value: 120000 },
	],
]);

const bodySizePages = {
	"Body Sizes' Capacity": route("package.body_size.capacity.index"),
	"Packages' Body Sizes": route("package.body_size.capacity.body-sizes"),
	Machines: route("package.body_size.capacity.machines"),
};

function PackageBodySizeCapacityList() {
	const targetMin = 0;
	const target = 100;

	const [activeDraggableID, setActiveDraggableID] = useState(null);

	const {
		data: packages,
		isLoading: isLoadingPackages,
		errorMessage: errorMessagePackages,
		errorData: errorDataPackages,
		cancel: cancelPackages,
		fetch: fetchPackages,
	} = useFetch(route("api.package.all"));

	const [selectedPackage, setSelectedPackage] = useState(
		Object.values(packages || {})[0],
	);
	console.log(
		"🚀 ~ PackageBodySizeCapacityList ~ selectedPackage:",
		selectedPackage,
	);

	const handleSetSelectedPackage = (id) => {
		setSelectedPackage(packages[id]);
	};

	const [bodySizeVisibility, setBodySizeVisibility] = useState(() =>
		Object.fromEntries([...containers.keys()].map((id) => [id, true])),
	);

	const selectedBodySizeVisibility = Object.keys(bodySizeVisibility).filter(
		(key) => bodySizeVisibility[key],
	);

	const bodySizeOptions = [...containers.keys()].map((container) => {
		return {
			value: container,
		};
	});

	const handleBodySizeVisibilityChange = (selected) => {
		const newVisibility = {};
		containers.forEach((value, bodySizeId) => {
			if (bodySizeId) {
				newVisibility[bodySizeId] = selected.includes(bodySizeId);
			}
		});
		setBodySizeVisibility(newVisibility);
	};

	useEffect(() => {
		setBodySizeVisibility(
			Object.fromEntries([...containers.keys()].map((id) => [id, true])),
		);
	}, [containers]);

	const innerDroppables = {
		f1: { name: "F1", color: "var(--color-f1color)" },
		f2: { name: "F2", color: "var(--color-f2color)" },
		f3: { name: "F3", color: "var(--color-f3color)" },
	};

	// draggables stored as a Map
	const [draggables, setDraggables] = useState(initialDraggables);
	console.log("🚀 ~ PackageBodySizeCapacityList ~ draggables:", draggables);

	const activeDraggableObject = activeDraggableID
		? draggables.get(activeDraggableID)
		: null;

	// locations: keep the same structure
	const [locations, setLocations] = useState({});

	function duplicateDraggable(originalId) {
		console.log("🚀 ~ duplicateDraggable ~ originalId:", originalId);
		const newId = `${originalId.id}-${Date.now()}`;

		setDraggables((prev) => {
			const original = prev.get(originalId.id);
			if (!original) return prev;

			const newDraggables = new Map(prev);
			newDraggables.set(newId, { ...original, id: newId });
			return newDraggables;
		});

		setLocations((prev) => ({
			...prev,
			[newId]: prev[originalId],
		}));
	}

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

	const totalHiddenBodySize = Array.from(containers.entries()).reduce(
		(count, [bodySizeId]) => count + (!bodySizeVisibility[bodySizeId] ? 1 : 0),
		0,
	);

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

	const [selectedPage, setSelectedPage] = useState("Body Sizes' Capacity");

	const handleSelectPage = (name) => {
		setSelectedPage(name);

		router.visit(bodySizePages[name]);
	};

	return (
		<div className="relative overflow-auto">
			<Tabs
				options={Object.keys(bodySizePages)}
				selectedFactory={selectedPage}
				handleFactoryChange={handleSelectPage}
				tabClassName={"mb-2"}
			/>
			{/* Header */}
			<div className="p-2 flex justify-between bg-base-200 shadow-lg">
				{/* <h1>LFCSP</h1> */}
				<MultiSelectSearchableDropdown
					formFieldName="package_name"
					options={
						packages?.map((pkg) => ({
							value: pkg.package_name,
							original: pkg,
						})) || []
					}
					returnKey="original"
					defaultSelectedOptions={selectedPackage}
					onChange={setSelectedPackage}
					itemName="package"
					prompt="Select Package"
					singleSelect
					disableSelectedContainer
					contentClassName={"h-72"}
				/>

				<MultiSelectSearchableDropdown
					formFieldName="name"
					options={bodySizeOptions}
					defaultSelectedOptions={selectedBodySizeVisibility}
					onChange={handleBodySizeVisibilityChange}
					itemName="body size"
					prompt="Select body sizes to show"
					disableSearch
					contentClassName={"h-72"}
				/>

				<div>{totalHiddenBodySize} body sizes hidden</div>

				<button type="button" className="btn btn-primary">
					Save for Today
				</button>
			</div>

			<DndContext onDragStart={handleDragStart} onDragEnd={handleDragEnd}>
				<div
					className="grid gap-1"
					style={{
						gridTemplateColumns: `80px repeat(${Object.keys(innerDroppables).length}, minmax(0, 1fr))`,
					}}
				>
					<div className="font-bold p-2 border-b">Body Size</div>

					{Object.entries(innerDroppables).map(([innerId, innerData]) => (
						<div
							key={innerId}
							className="font-extrabold p-2 border-b text-left"
							style={{ color: innerData.color }}
						>
							{innerData.name}
						</div>
					))}

					{/* Rows per outerId */}
					{Array.from(containers.entries()).map(([outerId, outerData]) => {
						if (!bodySizeVisibility[outerId]) return null;

						const draggablesInOuter = getDraggablesInOuter(outerId);
						const wip = outerData.wip;
						const lot = outerData.lot;

						return (
							<React.Fragment key={outerId}>
								{/* OuterId column */}
								<div className="grid grid-cols-[80px_repeat(auto-fit,minmax(0,1fr))] p-2 border-r border-b border-base-content/20 flex-0 items-center font-medium">
									{outerId}
								</div>

								{/* InnerDroppables columns */}
								{Object.entries(innerDroppables).map(([innerId, innerData]) => {
									const combinedId = `${outerId}-${innerId}`;
									const totalWip = wip[innerId] || 0;

									const innerDraggables = Array.from(
										draggables.values(),
									).filter((d) => locations[d.id] === combinedId);

									const totalInnerValue = innerDraggables.reduce(
										(sum, d) => sum + d.value,
										0,
									);

									const potentialCapacityAddedActiveDraggable =
										totalInnerValue +
										(activeDraggableObject
											? activeDraggableObject.value *
												(locations[activeDraggableID] === combinedId ? -1 : 1)
											: 0);

									const utilPercentage =
										totalInnerValue > 0
											? ((totalWip / totalInnerValue) * 100).toFixed(2)
											: "0.00";

									const potentialUtilPercentage =
										potentialCapacityAddedActiveDraggable > 0
											? (
													(totalWip / potentialCapacityAddedActiveDraggable) *
													100
												).toFixed(2)
											: "0.00";

									const ratio = closenessRatio(
										potentialUtilPercentage,
										targetMin,
										target,
										1,
									);
									console.log(
										"🚀 ~ PackageBodySizeCapacityList ~ ratio:",
										combinedId,
										ratio,
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

									const isEmpty =
										!activeDraggableID && innerDraggables.length === 0;

									return (
										<Droppable
											key={combinedId}
											id={combinedId}
											data={{ color: innerData.color }}
										>
											<div
												className={clsx(
													"px-2 py-1 border-b border-b-base-content/20 border-l flex flex-col w-full h-full",
													{
														"bg-red-500/10": ratio === 0,
													},
												)}
												style={{ borderLeftColor: innerData.color }}
											>
												{/* WIP and Util labels */}
												<div
													className={clsx("flex justify-between mb-1 text-xs", {
														"opacity-75": isEmpty,
													})}
												>
													<div className="pl-1 flex w-50 flex-col gap-1">
														<div className="flex justify-between">
															<span>WIP</span>
															<span className="font-mono">
																{totalWip.toLocaleString()}
															</span>
														</div>
														<div className="flex justify-between">
															<span>Capacity</span>
															<span className="font-mono">
																{potentialCapacityAddedActiveDraggable.toLocaleString()}
															</span>
														</div>
													</div>

													<div className="text-lg items-end flex flex-col justify-end font-extrabold">
														<div
															className={clsx(
																"flex-1 line-through text-xs mr-1",
																{
																	"opacity-0": !activeDraggableID,
																},
															)}
														>
															{utilPercentage.toLocaleString()}
														</div>
														<div
															className={clsx("flex-1", {
																"opacity-25": isEmpty,
															})}
															style={isEmpty ? {} : textColor}
														>
															{isEmpty ? "-" : `${potentialUtilPercentage}%`}
														</div>
													</div>
												</div>

												{/* Draggables */}
												<div className="flex flex-col gap-px">
													{innerDraggables.length > 0
														? innerDraggables.map((d) => (
																<Draggable
																	key={d.id}
																	id={d.id}
																	containerClassName={clsx(
																		"h-8 w-full bg-base-100 border border-base-content/20",
																		{
																			"opacity-0": activeDraggableID === d.id,
																		},
																	)}
																>
																	<MachineDraggable
																		d={d}
																		updateDraggable={updateDraggable}
																		dupeFunction={duplicateDraggable}
																	/>
																</Draggable>
															))
														: dropHere()}
												</div>
											</div>
										</Droppable>
									);
								})}
							</React.Fragment>
						);
					})}
				</div>

				{/* Drag overlay */}
				<DragOverlay
					adjustScale={false}
					transition="transform 150ms cubic-bezier(0.2, 0, 0, 1)"
				>
					{activeDraggableID ? (
						<div
							className={clsx(
								"h-8 w-full bg-base-100 ring-2 rounded-lg ring-accent",
							)}
						>
							<MachineDraggable
								d={draggables.get(activeDraggableID)}
								updateDraggable={updateDraggable}
								isOverlay
							/>
						</div>
					) : null}
				</DragOverlay>

				{/* Unassigned droppable */}
				<Droppable
					id="unassigned"
					data={{ className: "mt-4 z-40 flex flex-1" }}
				>
					<div className="rounded-lg border border-base-content/20 min-h-20 bg-base-200 flex-1 p-2">
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
											"bg-base-300 w-full h-8 border-base-content/20",
											{ "opacity-0": activeDraggableID === d.id },
										)}
									>
										<MachineDraggable d={d} updateDraggable={updateDraggable} />
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
