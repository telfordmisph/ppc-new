import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import React, { useState } from "react";
import { FaSave } from "react-icons/fa";
import { router, usePage } from "@inertiajs/react";
import { useMutation } from "@/Hooks/useMutation";
import { useToast } from "@/Hooks/useToast";

const PartNameUpsert = () => {
    const toast = useToast();
    const { part } = usePage().props;
    const isEdit = !!part;

    const [partname, setPartname] = useState(part?.Partname || "");
    const [focusGroup, setFocusGroup] = useState(part?.Focus_grp || "");
    const [factory, setFactory] = useState(part?.Factory || "");
    const [pl, setPl] = useState(part?.PL || "PL1");
    const [packageName, setPackageName] = useState(part?.Packagename || "");
    const [leadCount, setLeadCount] = useState(part?.Leadcount || "");
    const [bodySize, setBodySize] = useState(part?.Bodysize || "");
    const [packageCategory, setPackageCategory] = useState(
        part?.Packagecategory || ""
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
            Partname: partname,
            Focus_grp: focusGroup,
            Factory: factory,
            PL: pl,
            Packagename: packageName,
            Leadcount: leadCount,
            Bodysize: bodySize,
            Packagecategory: packageCategory,
        };

        const url = isEdit
            ? route("api.partname.update", { id: part.ppc_partnamedb_id })
            : route("api.partname.store");

        const method = isEdit ? "PATCH" : "POST";

        try {
            const response = await mutate(url, {
                method,
                body: formData,
            });

            console.log("🚀 ~ handleUpsert ~ response:", response);

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
        setPartname("");
        setFocusGroup("");
        setFactory("");
        setPl("");
        setPackageName("");
        setLeadCount("");
        setBodySize("");
        setPackageCategory("");
    };

    return (
        <AuthenticatedLayout>
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
                        <legend className="fieldset-legend">Partname</legend>
                        <input
                            type="text"
                            className="w-64 input input-bordered"
                            placeholder="Type Partname"
                            value={partname}
                            onChange={(e) => setPartname(e.target.value)}
                            required
                        />
                    </fieldset>

                    {/* Focus Group */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">Focus Group</legend>
                        <input
                            type="text"
                            className="input input-bordered w-28"
                            placeholder="Type Focus Group"
                            value={focusGroup}
                            onChange={(e) => setFocusGroup(e.target.value)}
                            required
                        />
                        <p className="label">e.g. INT</p>
                    </fieldset>

                    {/* Factory */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">Factory</legend>
                        <input
                            type="text"
                            className="w-32 input input-bordered"
                            placeholder="Type Factory"
                            value={factory}
                            onChange={(e) => setFactory(e.target.value)}
                            required
                        />
                        <p className="label">e.g. F1</p>
                    </fieldset>

                    {/* PL */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">PL</legend>
                        <select
                            className="w-20 select select-bordered"
                            value={pl}
                            onChange={(e) => setPl(e.target.value)}
                            required
                        >
                            <option value="PL1">PL1</option>
                            <option value="PL6">PL6</option>
                        </select>
                    </fieldset>

                    {/* Package Name */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">
                            Package Name
                        </legend>
                        <input
                            type="text"
                            className="input input-bordered w-44"
                            placeholder="Type Package Name"
                            value={packageName}
                            onChange={(e) => setPackageName(e.target.value)}
                            required
                        />
                    </fieldset>

                    {/* Lead Count */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">Lead Count</legend>
                        <input
                            type="text"
                            className="input input-bordered w-28"
                            placeholder="Lead Count"
                            value={leadCount}
                            onChange={(e) => setLeadCount(e.target.value)}
                            required
                        />
                        <p className="label">e.g. SOIC_N</p>
                    </fieldset>

                    {/* Body Size */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">Body Size</legend>
                        <input
                            type="text"
                            className="input input-bordered w-44"
                            placeholder="Type Body Size"
                            value={bodySize}
                            onChange={(e) => setBodySize(e.target.value)}
                            required
                        />
                        <p className="label">e.g. 10X10X2</p>
                    </fieldset>

                    {/* Package Category */}
                    <fieldset className="fieldset">
                        <legend className="fieldset-legend">
                            Package Category
                        </legend>
                        <input
                            type="text"
                            className="w-32 input input-bordered"
                            placeholder="Type Package Category"
                            value={packageCategory}
                            onChange={(e) => setPackageCategory(e.target.value)}
                            required
                        />
                        <p className="label">e.g. SOIC_N</p>
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
        </AuthenticatedLayout>
    );
};

export default PartNameUpsert;
