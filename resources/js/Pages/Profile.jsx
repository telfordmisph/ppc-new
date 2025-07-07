import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import Modal from "@/Components/Modal";
import InputError from "@/Components/InputError";
import InputLabel from "@/Components/InputLabel";
import TextInput from "@/Components/TextInput";

export default function Profile({ profile, errors }) {
    const { props } = usePage();
    const successMessage = props.flash?.success;

    const [password, setPassword] = useState({
        current_password: "",
        new_password: "",
        new_password_confirmation: "",
    });

    const [passwordForm, setPasswordForm] = useState(false);

    const handleChangePassword = () => {
        router.post(
            route("changePassword"),
            { ...password },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // router.visit(route("profile.index"));
                },
            }
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Profile" />

            <div className="max-w-3xl p-6 mx-auto border-[1px] rounded-2xl">
                <h1 className="pb-2 mb-6 text-2xl font-bold border-b">
                    User Profile
                </h1>

                {profile && (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <ProfileField label="Name" value={profile.EMPNAME} />
                        <ProfileField
                            label="Position"
                            value={profile.JOB_TITLE}
                        />
                        <ProfileField
                            label="Department"
                            value={profile.DEPARTMENT}
                        />
                        <ProfileField
                            label="Production Line"
                            value={profile.PRODLINE}
                        />
                        <ProfileField label="Station" value={profile.STATION} />
                        <ProfileField label="Email" value={profile.EMAIL} />
                        <div className="flex items-end gap-2">
                            <ProfileField
                                label="Password"
                                value={[...Array(profile.PASSWRD.length)]
                                    .map(() => "•")
                                    .join("")}
                            />
                            <button
                                className="border-blue-500 btn btn-sm border-[1px]"
                                onClick={() => setPasswordForm(true)}
                            >
                                Change Password
                            </button>
                        </div>
                    </div>
                )}

                {passwordForm && (
                    <div className="mt-6">
                        <div>
                            <InputLabel
                                htmlFor="old-password"
                                value="Old Password"
                            />
                            <TextInput
                                id="old-password"
                                type="password"
                                name="old-password"
                                value={password.current_password}
                                className="block w-full mt-1"
                                isFocused={true}
                                onChange={(e) =>
                                    setPassword({
                                        ...password,
                                        current_password: e.target.value,
                                    })
                                }
                            />

                            <InputError
                                message={errors.current_password}
                                className="mt-1"
                            />
                        </div>

                        <div className="mt-4">
                            <InputLabel
                                htmlFor="new-password"
                                value="New Password"
                            />
                            <TextInput
                                id="new-password"
                                type="text"
                                name="new-password"
                                value={password.new_password}
                                className="block w-full mt-1"
                                isFocused={true}
                                onChange={(e) =>
                                    setPassword({
                                        ...password,
                                        new_password: e.target.value,
                                    })
                                }
                            />

                            <InputError
                                message={errors.new_password}
                                className="mt-1"
                            />
                        </div>

                        <div className="mt-4">
                            <InputLabel
                                htmlFor="confirm-new-password"
                                value="Confirm New Password"
                            />
                            <TextInput
                                id="confirm-new-password"
                                type="text"
                                name="confirm-new-password"
                                value={password.new_password_confirmation}
                                className="block w-full mt-1"
                                isFocused={true}
                                onChange={(e) =>
                                    setPassword({
                                        ...password,
                                        new_password_confirmation:
                                            e.target.value,
                                    })
                                }
                            />

                            <InputError
                                message={errors.new_password_confirmation}
                                className="mt-1"
                            />
                        </div>

                        <button
                            className="w-full mt-4 btn btn-primary"
                            onClick={() => handleChangePassword()}
                        >
                            Change Password
                        </button>

                        {successMessage && (
                            <div
                                role="alert"
                                className="mt-2 text-white bg-green-600 alert"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    className="w-6 h-6 stroke-current shrink-0"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <span>{successMessage}</span>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

// Reusable profile display component
function ProfileField({ label, value }) {
    return (
        <div>
            <label className="block text-sm font-medium text-gray-500">
                {label}
            </label>
            <p className="mt-1 font-semibold ">{value}</p>
        </div>
    );
}
