import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { usePage } from "@inertiajs/react";
import { useState } from "react";

const PartNameList = () => {
    const { partNames } = usePage().props; // fetched from controller
    const [maxItem, setMaxItem] = useState(10);

    const handleMaxItemChange = (value) => {
        setMaxItem(value);
    };

    return (
        <AuthenticatedLayout>
            <div className="px-4">
                <h1 className="mb-4 text-2xl font-bold">Part Names</h1>

                <div className="flex justify-between py-4">
                    <div className="dropdown dropdown-bottom">
                        <div tabIndex={0} role="button" className="m-1 btn">
                            {`Show ${maxItem} items`}
                        </div>
                        <ul
                            tabIndex={0}
                            className="p-2 shadow-sm dropdown-content menu bg-base-100 rounded-box z-1 w-52"
                        >
                            {[10, 25, 50, 100].map((item) => (
                                <li key={item}>
                                    <a
                                        className="flex items-center justify-between"
                                        onClick={() =>
                                            handleMaxItemChange(item)
                                        }
                                    >
                                        {item}
                                        {maxItem === item && (
                                            <span className="font-bold text-green-500">
                                                ✔
                                            </span>
                                        )}
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <label className="input">
                        <svg
                            className="h-[1em] opacity-50"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                        >
                            <g
                                strokeLinejoin="round"
                                strokeLinecap="round"
                                strokeWidth="2.5"
                                fill="none"
                                stroke="currentColor"
                            >
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                            </g>
                        </svg>
                        <input type="search" required placeholder="Search" />
                    </label>
                </div>

                <table className="table w-full table-auto table-xs">
                    <thead>
                        <tr>
                            <th className="px-4 py-2">ID</th>
                            <th className="px-4 py-2">Part Name</th>
                            <th className="px-4 py-2">Focus Group</th>
                            <th className="px-4 py-2">Factory</th>
                            <th className="px-4 py-2">PL</th>
                            <th className="px-4 py-2">Package Name</th>
                            <th className="px-4 py-2">Lead Count</th>
                            <th className="px-4 py-2">Body Size</th>
                            <th className="px-4 py-2">Package</th>
                            <th className="px-4 py-2">Added By</th>
                            <th className="px-4 py-2">Date Created</th>
                            <th className="px-4 py-2">Actions</th>
                            {/* Add other columns as needed */}
                        </tr>
                    </thead>
                    <tbody>
                        {partNames.data.map((part) => (
                            <tr key={part.ppc_partnamedb_id}>
                                <td className="px-4 py-2">
                                    {part.ppc_partnamedb_id}
                                </td>
                                <td className="px-4 py-2">
                                    {part?.Partname || "-"}
                                </td>
                                <td className="px-4 py-2">
                                    {part?.Focus_grp || "-"}
                                </td>
                                <td className="px-4 py-2">
                                    {part?.Factory || "-"}
                                </td>
                                <td className="px-4 py-2">{part?.PL || "-"}</td>
                                <td className="px-4 py-2">
                                    {part?.Packagename || "-"}
                                </td>
                                <td className="px-4 py-2">
                                    {part?.Leadcount || "-"}
                                </td>
                                <td className="px-4 py-2">
                                    {part?.Bodysize || "-"}
                                </td>
                                <td className="px-4 py-2">
                                    {part?.Package || "-"}
                                </td>
                                <td className="px-4 py-2">
                                    {part?.added_by || "-"}
                                </td>
                                <td className="px-4 py-2">
                                    {(
                                        part?.date_created || "-"
                                    ).toLocaleString()}
                                </td>
                                <td className="px-4 py-2">
                                    <button className="btn btn-sm">Edit</button>
                                    <button className="btn btn-sm text-error">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                <div className="flex w-full mt-4 place-content-center join">
                    {partNames.links.map((link, index) => (
                        <a
                            key={index}
                            href={link.url}
                            className={`join-item btn rounded ${
                                link.active ? "bg-blue-500 text-white" : ""
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
};

export default PartNameList;
