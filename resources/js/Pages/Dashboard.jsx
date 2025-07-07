import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";

export default function Dashboard({ tableData, tableFilters }) {
    const props = usePage().props;

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <pre>{JSON.stringify(props.emp_data, null, 2)}</pre>
        </AuthenticatedLayout>
    );
}
