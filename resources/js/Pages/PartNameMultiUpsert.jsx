import React, { useMemo, useState } from "react";
import { FaSave, FaTimes, FaPlus } from "react-icons/fa";
import { router, usePage } from "@inertiajs/react";
import { useMutation } from "@/Hooks/useMutation";
import { useToast } from "@/Hooks/useToast";

const PartNameMultiInsert = () => {
	const toast = useToast();
	const { parts: serverParts } = usePage().props;
	console.log("🚀 ~ PartNameMultiInsert ~ part:", serverParts);
	const emptyPart = {
		Partname: "",
		Focus_grp: "",
		Factory: "",
		PL: "PL1",
		Packagename: "",
		Leadcount: "",
		Bodysize: "",
		Packagecategory: "",
	};

	const initialPartnames = useMemo(() => {
		console.log("changed");

		if (serverParts?.length) {
			return serverParts.map((p) => ({
				Partname: p.Partname || "",
				Focus_grp: p.Focus_grp || "",
				Factory: p.Factory || "",
				PL: p.PL || "PL1",
				Packagename: p.Packagename || "",
				Leadcount: p.Leadcount || "",
				Bodysize: p.Bodysize || "",
				Packagecategory: p.Packagecategory || "",
			}));
		}

		return [{ ...emptyPart }];
	}, [serverParts]);

	const [parts, setParts] = useState(initialPartnames);

	const {
		mutate,
		isLoading: isMutateLoading,
		errorMessage: mutateErrorMessage,
	} = useMutation();

	const handleInputChange = (index, field, value) => {
		setParts((prev) =>
			prev.map((part, i) => (i === index ? { ...part, [field]: value } : part)),
		);
	};

	const handleAddRow = () => setParts([...parts, { ...emptyPart }]);
	const handleRemoveRow = (index) =>
		setParts(parts.filter((_, i) => i !== index));

	const handleReset = () => {
		console.log("🚀 ~ handleReset ~ initialPartnames:", initialPartnames);

		setParts(initialPartnames);
	};

	const handleUpsert = async (e) => {
		e.preventDefault();

		const url = route("api.partname.store");
		const method = "POST";

		try {
			console.log("🚀 ~ PartNameMultiInsert ~ parssssssssts:", parts);

			await mutate(url, { method, body: parts });
			toast.success("Parts created successfully!");
			router.visit(route("partname.index"));
		} catch (err) {
			console.error("Upsert failed:", err?.message);
			toast.error(err?.message);
		}
	};

	return (
		<>
			<h1 className="text-base font-bold mb-4">Add New Parts</h1>
			<form onSubmit={handleUpsert}>
				<div className="overflow-x-auto">
					<table className="table table-zebra w-full">
						<thead>
							<tr>
								<th>#</th>
								<th className="w-[220px]">Partname</th>
								<th className="w-[120px]">Focus Group</th>
								<th className="w-3.5">Factory</th>
								<th className="w-[120px]">PL</th>
								<th>Package Name</th>
								<th className="w-[30px]">Lead Count</th>
								<th>Body Size</th>
								<th>Package Category</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							{parts.map((p, idx) => (
								<tr key={idx}>
									<th>{idx + 1}</th>
									<td>
										<input
											type="text"
											className="input input-bordered w-full"
											value={p.Partname}
											onChange={(e) =>
												handleInputChange(idx, "Partname", e.target.value)
											}
											required
										/>
									</td>
									<td>
										<input
											type="text"
											className="input input-bordered w-full"
											value={p.Focus_grp}
											onChange={(e) =>
												handleInputChange(idx, "Focus_grp", e.target.value)
											}
											required
										/>
									</td>
									<td>
										<input
											type="text"
											className="input input-bordered w-full"
											value={p.Factory}
											onChange={(e) =>
												handleInputChange(idx, "Factory", e.target.value)
											}
											required
										/>
									</td>
									<td>
										<select
											className="select select-bordered w-full"
											value={p.PL}
											onChange={(e) =>
												handleInputChange(idx, "PL", e.target.value)
											}
											required
										>
											<option value="PL1">PL1</option>
											<option value="PL6">PL6</option>
										</select>
									</td>
									<td>
										<input
											type="text"
											className="input input-bordered w-full"
											value={p.Packagename}
											onChange={(e) =>
												handleInputChange(idx, "Packagename", e.target.value)
											}
											required
										/>
									</td>
									<td>
										<input
											type="text"
											className="input input-bordered w-full"
											value={p.Leadcount}
											onChange={(e) =>
												handleInputChange(idx, "Leadcount", e.target.value)
											}
											required
										/>
									</td>
									<td>
										<input
											type="text"
											className="input input-bordered w-full"
											value={p.Bodysize}
											onChange={(e) =>
												handleInputChange(idx, "Bodysize", e.target.value)
											}
										/>
									</td>
									<td>
										<input
											type="text"
											className="input input-bordered w-full"
											value={p.Packagecategory}
											onChange={(e) =>
												handleInputChange(
													idx,
													"Packagecategory",
													e.target.value,
												)
											}
										/>
									</td>
									<td className="text-center">
										<button
											type="button"
											onClick={() => handleRemoveRow(idx)}
											className="btn btn-error btn-sm"
										>
											<FaTimes />
										</button>
									</td>
								</tr>
							))}
						</tbody>
					</table>
				</div>

				<div className="flex gap-2 mt-4">
					<button
						type="button"
						onClick={handleAddRow}
						className="btn btn-outline btn-accent"
					>
						<FaPlus /> Add Row
					</button>

					<button
						type="button"
						onClick={handleReset}
						className="btn btn-outline btn-error"
					>
						Reset
					</button>

					<button
						type="submit"
						className="btn btn-primary"
						disabled={isMutateLoading}
					>
						{isMutateLoading ? (
							<span className="loading loading-spinner"></span>
						) : (
							<FaSave />
						)}
						Save All
					</button>
				</div>
			</form>
		</>
	);
};

export default PartNameMultiInsert;
