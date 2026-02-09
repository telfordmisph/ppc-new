import MultiInputList from "@/Components/MultipleInputList";
import { useMutation } from "@/Hooks/useMutation";
import { useToast } from "@/Hooks/useToast";
import { router, usePage } from "@inertiajs/react";
import { useState } from "react";
import { FaSave } from "react-icons/fa";

const PackageGroupUpsert = () => {
	const toast = useToast();
	const { packageGroup } = usePage().props;
	const isEdit = !!packageGroup;
	const [factory, setFactory] = useState(
		packageGroup?.factory.toUpperCase() || "F1",
	);
	const [groupName, setGroupName] = useState(packageGroup?.group_name || "");
	const [packages, setPackages] = useState(
		packageGroup?.packages.map((p) => p.package_name) || [],
	);

	const {
		mutate,
		isLoading: isMutateLoading,
		errorMessage: mutateErrorMessage,
		cancel: mutateCancel,
	} = useMutation();

	const handleItemsChange = (updatedItems) => {
		setPackages(updatedItems);
	};

	const handleUpsert = async (e) => {
		e.preventDefault();

		if (packages.length === 0) {
			toast.error(
				"Please add at least one package. Click the 'Add' button if you have filled the first row.",
			);

			return;
		}

		const formData = {
			id: packageGroup?.id || null,
			factory: factory,
			group_name: groupName || "",
			packageName: packages.join(","),
		};

		const url = isEdit
			? route("api.package.update", {
					id: packageGroup.id,
				})
			: route("api.package.store");

		const method = isEdit ? "PATCH" : "POST";

		try {
			const response = await mutate(url, {
				method,
				body: formData,
			});

			toast.success(
				isEdit
					? "Package group updated successfully!"
					: "Package group created successfully!",
			);

			router.visit(route("package.group.index"));
		} catch (err) {
			console.error("Upsert failed:", mutateErrorMessage);
			toast.error(err.message);
		}
	};

	const handleReset = () => {
		setPackages([]);
		setGroupName("");
	};

	return (
		<>
			<h1 className="text-base font-bold">
				{isEdit ? "Edit Package Group" : "Add New Package Group"}
			</h1>
			<div>
				<form
					onSubmit={handleUpsert}
					className="max-w-lg p-4 space-y-4 rounded-lg"
					method="POST"
				>
					<fieldset className="fieldset">
						<legend className="fieldset-legend">Factory</legend>
						<select
							className="w-20 select select-bordered"
							value={factory}
							onChange={(e) => setFactory(e.target.value)}
							required
						>
							<option value="F1">F1</option>
							<option value="F2">F2</option>
							<option value="F3">F3</option>
						</select>
					</fieldset>

					<fieldset className="fieldset">
						<legend className="fieldset-legend">Package Name</legend>

						<MultiInputList
							selectedItems={packages}
							ItemLabel="package name"
							onChange={handleItemsChange}
						/>

						<p className="label">Press Enter key or click the 'Add' button</p>
						<p className="label">A package name cannot be in multiple group</p>
					</fieldset>

					<fieldset className="fieldset">
						<legend className="fieldset-legend">Group Name</legend>
						<input
							type="text"
							className="input input-bordered w-52"
							placeholder="Type Group Name"
							value={groupName}
							onChange={(e) => setGroupName(e.target.value)}
						/>
						<p className="label">optional</p>
					</fieldset>

					<div className="flex mt-4 space-x-2">
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
							{isEdit ? "Edit Package Group" : "Add Package Group"}
						</button>
					</div>
				</form>
			</div>
		</>
	);
};

export default PackageGroupUpsert;
