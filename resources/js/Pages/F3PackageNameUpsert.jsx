import React, { useState } from "react";
import { FaSave } from "react-icons/fa";
import { router, usePage } from "@inertiajs/react";
import { useMutation } from "@/Hooks/useMutation";
import { useToast } from "@/Hooks/useToast";

const F3RawPackageUpsert = () => {
    const toast = useToast();
    const { selectedRawPackage } = usePage().props;
    const isEdit = !!selectedRawPackage;

    const [rawPackage, setRawPackage] = useState(
        selectedRawPackage?.raw_package || ""
    );
    const [factory, setFactory] = useState(selectedRawPackage?.Factory || "");
    const [packageName, setPackageName] = useState(
        selectedRawPackage?.Packagename || ""
    );
    const [leadCount, setLeadCount] = useState(
        selectedRawPackage?.lead_count || ""
    );
    const [dimension, setDimension] = useState(
        selectedRawPackage?.dimension || ""
    );

    const {
        mutate,
        isLoading: isMutateLoading,
        errorMessage: mutateErrorMessage,
        cancel: mutateCancel,
    } = useMutation();

    const handleUpsert = async (e) => {
        e.preventDefault();

        const formData = {
            Partname: rawPackage,
            Focus_grp: focusGroup,
            Factory: factory,
            Packagename: packageName,
            Leadcount: leadCount,
            Bodysize: dimension,
        };

        const url = isEdit
            ? route("api.partname.update", {
                  id: selectedRawPackage.ppc_partnamedb_id,
              })
            : route("api.partname.store");

        const method = isEdit ? "PATCH" : "POST";

        try {
            const response = await mutate(url, {
                method,
                body: formData,
            });

            toast.success(
                isEdit
                    ? "Part updated successfully!"
                    : "Part created successfully!"
            );

            router.visit(route("partname.index"));
        } catch (err) {
            console.error("Upsert failed:", mutateErrorMessage);
            toast.error(mutateErrorMessage);
        }
    };

    const handleReset = () => {
        setRawPackage("");
        setFactory("");
        setPackageName("");
        setLeadCount("");
        setDimension("");
    };

    return (
        <>
            <h1 className="text-base font-bold">
                {isEdit ? "Edit Part" : "Add New Part"}
            </h1>
            <div>
                <form
                    onSubmit={handleUpsert}
                    className="max-w-lg p-4 space-y-4 rounded-lg"
                    method="POST"
                >
                    {/* Partname */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">Raw Package</legend>
                        <input
                            type="text"
                            className="w-64 input input-bordered"
                            placeholder="Type Partname"
                            value={rawPackage}
                            onChange={(e) => setRawPackage(e.target.value)}
                            required
                        />
                        <p className="label">e.g. 16LTQFN3X3+5Q1</p>
                    </fieldset>

                    {/* Lead Count */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">Lead Count</legend>
                        <input
                            type="number"
                            className="input input-bordered w-28"
                            placeholder="Lead Count"
                            value={leadCount}
                            onChange={(e) => setLeadCount(e.target.value)}
                            required
                        />
                    </fieldset>

                    {/* Package Name */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">
                            Package Name
                        </legend>
                        {/* <MultiSelectDropdown
                            options={
                                packagesData?.data.map((opt) => ({
                                    value: opt,
                                    label: null,
                                })) || []
                            }
                            onChange={(value) => {}}
                            isLoading={isPackagesLoading}
                            itemName="Package List"
                            prompt="Select packages"
                            contentClassName="w-52 h-50"
                        /> */}
                        {/* <input
                            type="text"
                            className="input input-bordered w-44"
                            placeholder="Type Package Name"
                            value={packageName}
                            onChange={(e) => setPackageName(e.target.value)}
                            required
                        /> */}
                    </fieldset>

                    {/* Body Size */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">Body Size</legend>
                        <input
                            type="text"
                            className="input input-bordered w-44"
                            placeholder="Type Body Size"
                            value={dimension}
                            onChange={(e) => setDimension(e.target.value)}
                            required
                        />
                        <p className="label">e.g. 10X10X2</p>
                    </fieldset>

                    {/* Buttons */}
                    <div className="flex mt-4 space-x-2">
                        <button
                            type="button"
                            onClick={handleReset}
                            className="btn btn-outline btn-error"
                        >
                            Reset
                        </button>
                        <button type="submit" className="btn btn-primary">
                            {isMutateLoading ? (
                                <span className="loading loading-spinner"></span>
                            ) : (
                                <FaSave />
                            )}
                            {isEdit ? "Edit" : "Add"}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
};

export default F3RawPackageUpsert;
