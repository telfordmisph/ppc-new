import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import DataTable from "@/Components/DataTable";
import Modal from "@/Components/Modal";

import { useState } from "react";

export default function Admin({ tableData, tableFilters }) {
    const props = usePage().props;

    const [selectedRows, setSelectedRows] = useState([]);

    return (
        <AuthenticatedLayout>
            <Head title="Admin" />

            <h1 className="text-2xl font-bold">Administrators</h1>

            <DataTable
                columns={[
                    { key: "emp_id", label: "ID" },
                    { key: "emp_name", label: "Employee Name" },
                    { key: "emp_role", label: "Role" },
                ]}
                data={tableData.data}
                meta={{
                    from: tableData.from,
                    to: tableData.to,
                    total: tableData.total,
                    links: tableData.links,
                    currentPage: tableData.current_page,
                    lastPage: tableData.last_page,
                }}
                routeName={route("admin")}
                filters={tableFilters}
                rowKey="EMPLOYID"
                // selectable={true}
                // onSelectionChange={setSelectedRows}
                // dateRangeSearch={true}
                showExport={false}
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
        </AuthenticatedLayout>
    );
}
