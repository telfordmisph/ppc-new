import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import ChartTest from "./ChartTest";

export default function Dashboard() {
    const props = usePage().props;

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />
            <pre>{JSON.stringify(props.emp_data, null, 2)}</pre>
        </AuthenticatedLayout>
    );
}
