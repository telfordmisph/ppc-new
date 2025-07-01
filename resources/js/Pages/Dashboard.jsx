import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import ChartTest from "./ChartTest";
import DataTable from "@/Components/DataTable";
import Modal from "@/Components/Modal";

import { useState } from "react";

export default function Dashboard({ data, filters }) {
    const props = usePage().props;

    console.log("Dashboard props:", data.meta);

    const [selectedRows, setSelectedRows] = useState([]);

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />
            <pre>{JSON.stringify(props.emp_data, null, 2)}</pre>
            <Modal
                id="TestModal"
                title="Confirm Logout?"
                buttonText={"Text or Icon here"}
                buttonClass="btn"
                className=""
            >
                <p className="">modal content body</p>

                <div className="flex justify-end mt-1">
                    <button className="btn">Action Button</button>
                </div>
            </Modal>

            <DataTable
                columns={[
                    { key: "EMPLOYID", label: "ID" },
                    { key: "EMPNAME", label: "Employee Name" },
                    { key: "JOB_TITLE", label: "Job Title" },
                ]}
                data={data.data}
                meta={{
                    from: data.from,
                    to: data.to,
                    total: data.total,
                    links: data.links,
                    currentPage: data.current_page,
                    lastPage: data.last_page,
                }}
                routeName={route("dashboard")}
                filters={filters}
                rowKey="EMPLOYID"
                selectable={true}
                onSelectionChange={setSelectedRows}
                dateRangeSearch={true}
            >
                {(row, close) => (
                    <Modal
                        id="RowModal"
                        title={`Employee Details - ${row.EMPNAME}`}
                        show={true}
                        onClose={close}
                    >
                        {/* <pre>{JSON.stringify(row, null, 2)}</pre> */}

                        <p>
                            <strong>ID:</strong> {row.EMPLOYID}
                        </p>
                        <p>
                            <strong>Name:</strong> {row.EMPNAME}
                        </p>
                        <p>
                            <strong>Job Title:</strong> {row.JOB_TITLE}
                        </p>

                        <div className="flex justify-end mt-4">
                            <button className="btn" onClick={close}>
                                Close
                            </button>
                        </div>
                    </Modal>
                )}
            </DataTable>

            <div className="mt-4">
                <h2 className="text-lg font-semibold">Selected Rows</h2>
                <pre>{JSON.stringify(selectedRows, null, 2)}</pre>
            </div>
        </AuthenticatedLayout>
    );
}
