import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import ChartTest from "./ChartTest";
import Modal from "@/Components/Modal";

export default function Dashboard() {
    const props = usePage().props;

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
        </AuthenticatedLayout>
    );
}
