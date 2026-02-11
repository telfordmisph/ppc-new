import CancellableActionButton from "@/Components/CancellableActionButton";
import { Draggable } from "@/Components/DnD/Draggable";
import { Droppable } from "@/Components/DnD/Droppable";
import { MachineDraggable } from "@/Components/DnD/MachineDraggable";
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";
import Tabs from "@/Components/Tabs";
import { useMutation } from "@/Hooks/useMutation";
import { DndContext, DragOverlay } from "@dnd-kit/core";
import { router, usePage } from "@inertiajs/react";
import clsx from "clsx";
import React, { useCallback, useEffect, useState } from "react";
import toast from "react-hot-toast";

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

const dropHere = () => {
	return null;
	// <div className="opacity-25 flex flex-1 items-center justify-center rounded border border-dashed text-sm h-full">
	// 	Drop here
	// </div>
};

const factoryDroppables = {
	f1: { name: "F1", color: "var(--color-f1color)" },
	f2: { name: "F2", color: "var(--color-f2color)" },
	f38mm: { name: "F3 8mm", color: "var(--color-f3color)" },
	f312mm: { name: "F3 12mm", color: "var(--color-f3color)" },
};

const bodySizePages = {
	"Body Sizes' Capacity": route("package.body_size.capacity.index"),
	"Body Sizes": route("package.body_size.capacity.body-sizes"),
	Machines: route("package.body_size.capacity.machines"),
};

const UNASSIGNED = "unassigned";

function PackageBodySizeCapacityList() {
	const { profiles, bodySizes, machines } = usePage().props;
	console.log("🚀 ~ PackageBodySizeCapacityList ~ machines:", machines);

	const containers = React.useMemo(
		() => new Map(bodySizes.map((item) => [item.key, item.value])),
		[bodySizes],
	);
	console.log("🚀 ~ PackageBodySizeCapacityList ~ containers:", containers);

	const initializeDraggables = useCallback(() => {
		const machineDraggables = new Map();

		machines?.forEach((machine) => {
			const capacityProfile = machine?.capacity_profiles ?? [];
			const machineId = machine.id;

			if (capacityProfile.length === 0) {
				const id = `machine-${machineId}`;

				machineDraggables.set(id, {
					id: id,
					machineId: machineId,
					profileId: null,
					name: machine?.name ?? null,
					value: 0,
					bodySizeName: null,
					factory: null,
				});

				return;
			}

			capacityProfile.forEach((profile) => {
				const profileID = profile.id;
				const factory = profile?.factory ?? null;
				const id = `machine-${machineId}-${profileID}`;

				machineDraggables.set(id, {
					id,
					machineId: machineId,
					profileId: profileID,
					name: machine?.name ?? null,
					value: Number(profile?.capacity) || 0,
					bodySizeName: profile?.body_size?.name ?? null,
					factory: typeof factory === "string" ? factory.toLowerCase() : null,
				});
			});
		});

		return machineDraggables;
	}, [machines]);

	const initialDraggables = React.useMemo(() => {
		return initializeDraggables();
	}, [initializeDraggables]);

	const [draggables, setDraggables] = useState(initialDraggables);
	const [locations, setLocations] = useState({});
	console.log("🚀 ~ PackageBodySizeCapacityList ~ draggables:", draggables);

	const initializedLocations = useCallback(() => {
		setLocations((prev) => {
			const next = { ...prev };

			for (const [id, item] of draggables) {
				const { bodySizeName, factory } = item;

				if (!bodySizeName || !factory) {
					next[id] ??= null;
				} else {
					next[id] = `${bodySizeName}-${factory}`;
				}
			}

			return next;
		});
	}, [draggables]);

	const handleReset = () => {
		const newDraggables = initializeDraggables();
		setDraggables(newDraggables);

		const nextLocations = {};
		for (const [id, item] of newDraggables) {
			const { bodySizeName, factory } = item;
			nextLocations[id] =
				bodySizeName && factory ? `${bodySizeName}-${factory}` : null;
		}
		setLocations(nextLocations);
	};

	// console.log("🚀 ~ PackageBodySizeCapacityList ~ draggables:", draggables);
	console.log("🚀 ~ PackageBodySizeCapacityList ~ locations:", locations);
	// console.log("🚀 ~ PackageBodySizeCapacityList ~ machines:", machines);
	// console.log("🚀 ~ containers:", containers);
	// console.log("🚀 ~ containersOLD:", containersOLD);

	const targetMin = 0;
	const target = 100;

	const [activeDraggableID, setActiveDraggableID] = useState(null);

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

	const activeDraggableObject = activeDraggableID
		? draggables.get(activeDraggableID)
		: null;

	const hasInitialized = React.useRef(false);

	useEffect(() => {
		if (hasInitialized.current) return;
		if (!draggables.size) return;

		hasInitialized.current = true;

		initializedLocations();
	}, [draggables, initializedLocations]);

	function handleDragStart(event) {
		setActiveDraggableID(event.active.id);
	}

	function handleDragEnd(event) {
		const { active, over } = event;

		const newBodySizeName = over ? over.data.current.bodySizeName : null;
		const newFactory = over ? over.data.current.factoryId : null;

		setLocations((prev) => ({
			...prev,
			[active.id]: over ? (over.id === UNASSIGNED ? null : over.id) : null,
		}));

		setDraggables((prev) => {
			const item = prev.get(active.id);
			if (!item) return prev;

			const next = new Map(prev);

			next.set(active.id, {
				...item,
				bodySizeName: newBodySizeName,
				factory: newFactory,
			});

			return next;
		});

		setActiveDraggableID(null);
	}

	const updateDraggable = useCallback((id, key, newValue) => {
		setDraggables((prev) => {
			const item = prev.get(id);
			if (!item) return prev;

			const next = new Map(prev);
			next.set(id, {
				...item,
				[key]: key === "value" ? parseInt(newValue) || 0 : newValue,
			});
			return next;
		});
	}, []);

	const duplicateDraggable = useCallback((original) => {
		const newId = `${original.id}-${Date.now()}`;
		const combinedId = `${original.bodySizeName}-${original.factory}`;

		setDraggables((prev) => {
			const originalItem = prev.get(original.id);
			if (!originalItem) return prev;

			const newDraggables = new Map(prev);
			newDraggables.set(newId, { ...originalItem, id: newId });
			return newDraggables;
		});

		setLocations((prev) => ({
			...prev,
			[newId]: combinedId,
		}));
	}, []);

	const unassignDraggable = useCallback((draggable) => {
		const id = draggable.id;
		console.log("🚀 ~ PackageBodySizeCapacityList ~ id:", id);

		setDraggables((prev) => {
			const item = prev.get(id);
			if (!item) return prev;

			const next = new Map(prev);

			next.set(id, {
				...item,
				bodySizeName: null,
				factory: null,
			});

			return next;
		});

		setLocations((prev) => ({
			...prev,
			[id]: null,
		}));
	}, []);

	const totalHiddenBodySize = Array.from(containers.entries()).reduce(
		(count, [bodySizeId]) => count + (!bodySizeVisibility[bodySizeId] ? 1 : 0),
		0,
	);

	const getDraggablesInOuter = (outerId) => {
		const innerIds = Object.keys(factoryDroppables).map(
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

	const {
		mutate: mutateBodySizeCapacity,
		isLoading: isMutateBodySizeCapacityLoading,
		errorMessage: mutateBodySizeCapacityErrorMessage,
		errorData: mutateBodySizeCapacityErrorData,
		cancel: mutateBodySizeCapacityCancel,
	} = useMutation(route("api.body-sizes.bulkUpdate"));

	const handleSave = async () => {
		const currentMachines = initializeDraggables();
		console.log("🚀 ~ handleSave ~ currentMachines:", currentMachines);

		const isTheSame = (oldItem, newItem) => {
			if (!oldItem) return false;

			return (
				newItem.bodySizeName === oldItem.bodySizeName &&
				newItem.factory === oldItem.factory &&
				newItem.machineId === oldItem.machineId &&
				newItem.value === oldItem.value
			);
		};

		const updatedCapacity = [];
		const expiredProfiles = [];
		Array.from(draggables.values()).forEach((updatedMachine) => {
			const oldMachine = currentMachines.get(updatedMachine.id);
			const isNewMachine = !oldMachine;

			const isUnchanged = isTheSame(oldMachine, updatedMachine);
			if (isUnchanged) return;

			const isUnassigned =
				oldMachine?.bodySizeName !== null &&
				updatedMachine.bodySizeName === null;

			if (updatedMachine.profileId !== null && !isNewMachine) {
				expiredProfiles.push(updatedMachine.profileId);
			}

			// console.log("🚀 ~ handleSave ~ oldMachine:", oldMachine);
			// console.log("🚀 ~ handleSave ~ newMachine:", updatedMachine);
			// console.log("🚀 ~ handleSave ~ isUnchanged:", isUnchanged);
			// console.log("🚀 ~ handleSave ~ isUnassigned:", isUnassigned);
			if (isUnassigned) return;

			const bodySize = containers.get(updatedMachine.bodySizeName) ?? null;

			updatedCapacity.push({
				body_size_id: bodySize.id,
				factory: updatedMachine.factory,
				capacity: updatedMachine.value,
				machine_id: updatedMachine.machineId,
			});
		});

		try {
			await mutateBodySizeCapacity(
				route("api.body-sizes.capacity.bulkUpsert"),
				{
					method: "PATCH",
					body: {
						rows: updatedCapacity,
						expire_profiles: expiredProfiles,
					},
				},
			);

			toast.success("Capacity updated successfully");
		} catch (error) {
			console.error(error);
			toast.error(error.message);
		}
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

				{totalHiddenBodySize > 0 && (
					<div>{totalHiddenBodySize} body sizes hidden</div>
				)}

				<div className="flex gap-2">
					<button
						type="button"
						className="btn btn-secondary"
						onClick={() => handleReset()}
					>
						Reset
					</button>

					<CancellableActionButton
						abort={mutateBodySizeCapacityCancel}
						refetch={handleSave}
						loading={isMutateBodySizeCapacityLoading}
						buttonText={"Save for Today"}
						loadingMessage="Saving"
					/>
				</div>
			</div>

			<DndContext onDragStart={handleDragStart} onDragEnd={handleDragEnd}>
				<div
					className="grid gap-1"
					style={{
						gridTemplateColumns: `50px repeat(${Object.keys(factoryDroppables).length}, minmax(0, 1fr))`,
					}}
				>
					<div className="font-bold p-2 border-b">Body Size</div>

					{Object.entries(factoryDroppables).map(([factoryId, factoryData]) => (
						<div
							key={factoryId}
							className="font-extrabold p-2 border-b text-left"
							style={{ color: factoryData.color }}
						>
							{factoryData.name}
						</div>
					))}

					{/* Rows per outerId */}
					{Array.from(containers.entries()).map(
						([bodySizeNameId, bodySizeData]) => {
							if (!bodySizeVisibility[bodySizeNameId]) return null;

							const draggablesInOuter = getDraggablesInOuter(bodySizeNameId);
							const wip = bodySizeData.wip;
							const lot = bodySizeData.lot;

							return (
								<React.Fragment key={bodySizeNameId}>
									{/* OuterId column */}
									<div className="grid grid-cols-[50px_repeat(auto-fit,minmax(0,1fr))] border-r border-b border-base-content/20 flex-0 items-center font-medium">
										{bodySizeNameId}
									</div>

									{/* InnerDroppables columns */}
									{Object.entries(factoryDroppables).map(
										([factoryId, factoryData]) => {
											const combinedId = `${bodySizeNameId}-${factoryId}`;
											const totalWip = wip[factoryId] || 0;
											const totalLot = lot[factoryId] || 0;

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
														(locations[activeDraggableID] === combinedId
															? -1
															: 1)
													: 0);

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
													: "0.00";

											const ratio = closenessRatio(
												potentialUtilPercentage,
												targetMin,
												target,
												1,
											);

											const alpha = 0.2; // 0 = transparent, 1 = solid
											const defaultColor = [255, 0, 0];
											const green = [0, 255, 0];
											const textColor = {
												backgroundColor: `rgb(
                    ${lerp(defaultColor[0], green[0], ratio)},
                    ${lerp(defaultColor[1], green[1], ratio)},
                    ${lerp(defaultColor[2], green[2], ratio)},
										${alpha}
                  )`,
											};

											const isEmpty =
												!activeDraggableID && innerDraggables.length === 0;

											return (
												<Droppable
													key={combinedId}
													id={combinedId}
													data={{
														color: factoryData.color,
														bodySizeName: bodySizeData.name,
														factoryId: factoryId,
													}}
												>
													<div
														className={clsx(
															"py-1 border-b border-b-base-content/20 border-l flex flex-col w-full h-full",
															{
																"bg-red-500/10": ratio === 0,
															},
														)}
														style={{
															borderLeftColor: factoryData.color,
															...(isEmpty ? {} : textColor),
														}}
													>
														{/* WIP and Util labels */}
														<div
															className={clsx(
																"flex justify-between mb-1 px-2 text-xs",
																{
																	"opacity-75": isEmpty,
																},
															)}
														>
															<div className="pl-1 flex w-40 flex-col">
																<div className="flex justify-between">
																	<span className="opacity-50">WIP</span>
																	<span className="font-mono">
																		{totalWip.toLocaleString()}
																	</span>
																</div>
																<div className="flex justify-between">
																	<span className="opacity-50">CAP</span>
																	<span className="font-mono">
																		{potentialCapacityAddedActiveDraggable.toLocaleString()}
																	</span>
																</div>
																<div className="flex justify-between">
																	<span className="opacity-50">LOT</span>
																	<span className="font-mono">
																		{totalLot.toLocaleString()}
																	</span>
																</div>
															</div>

															<div className="text-md items-end flex flex-col justify-end font-extrabold">
																<div
																	className={clsx("flex-1 text-base-content", {
																		"opacity-25": isEmpty,
																	})}
																>
																	{isEmpty
																		? "-"
																		: `${potentialUtilPercentage}%`}
																</div>
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
															</div>
														</div>

														{/* Draggables */}
														<div className="flex flex-col">
															{innerDraggables.length > 0
																? innerDraggables.map((d) => (
																		<Draggable
																			key={d.id}
																			id={d.id}
																			data={{
																				draggable: d,
																				bodySizeName: bodySizeNameId,
																			}}
																			containerClassName={clsx(
																				"h-7 w-full bg-base-100 border border-base-content/20",
																				{
																					"opacity-0":
																						activeDraggableID === d.id,
																				},
																			)}
																		>
																			<MachineDraggable
																				d={d}
																				updateDraggable={updateDraggable}
																				dupeFunction={duplicateDraggable}
																				unassignFunction={unassignDraggable}
																			/>
																		</Draggable>
																	))
																: dropHere()}
														</div>
													</div>
												</Droppable>
											);
										},
									)}
								</React.Fragment>
							);
						},
					)}
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
					id={UNASSIGNED}
					data={{
						className: "mt-4 z-40 flex flex-1",
						bodySizeName: null,
						factoryId: null,
					}}
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
										data={{ draggable: d, bodySizeName: UNASSIGNED }}
										containerClassName={clsx(
											"bg-base-300 w-40 h-8 border-base-content/20",
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
