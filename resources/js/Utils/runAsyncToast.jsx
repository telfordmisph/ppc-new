import { toast } from "react-hot-toast";
import clsx from "clsx";

export function runAsyncToast({
    action,
    loadingMessage,
    successMessage,
    renderSuccess,
    errorMessage,
    duration,
}) {
    const toastId = "async-toast";

    const toastTransition = (t) =>
        clsx(
            "transition-all duration-300",
            t.visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-2"
        );

    toast.custom(
        (t) => (
            <div
                className={clsx(
                    "text-sm flex items-center gap-2 p-4 m-2 rounded-lg shadow-lg bg-base-100",
                    toastTransition(t)
                )}
            >
                <span className="loading loading-spinner loading-xs" />
                <span>{loadingMessage || "Processing..."}</span>
            </div>
        ),
        { id: toastId, duration: Infinity, removeDelay: 400 }
    );

    return action()
        .then((result) => {
            console.log("🚀 ~ runAsyncToast ~ result:", result);

            toast.dismiss(toastId);

            toast.custom(
                (t) =>
                    renderSuccess ? (
                        <div
                            className={clsx(
                                "text-sm m-2 p-4 rounded-lg shadow-lg bg-base-100",
                                toastTransition(t)
                            )}
                        >
                            {renderSuccess(result)}

                            <button
                                className="mt-3 btn btn-wide btn-sm"
                                onClick={() => toast.dismiss(t.id)}
                            >
                                Close
                            </button>
                        </div>
                    ) : (
                        <div
                            className={clsx(
                                "text-sm m-2 p-4 rounded-lg shadow-lg bg-base-100",
                                toastTransition(t)
                            )}
                        >
                            {successMessage || "Action completed successfully!"}
                        </div>
                    ),
                { duration: Infinity, removeDelay: 400 }
            );

            return result;
        })
        .catch((err) => {
            toast.dismiss(toastId);

            toast.custom(
                (t) => (
                    <div
                        className={clsx(
                            "text-sm p-4 m-2 w-60 rounded-lg shadow-lg text-error-content bg-error",
                            toastTransition(t)
                        )}
                    >
                        <div className="mb-1">
                            {err.message || "Action failed."}
                        </div>

                        <button
                            className="mt-3 btn btn-wide btn-sm"
                            onClick={() => toast.dismiss(t.id)}
                        >
                            Close
                        </button>
                    </div>
                ),
                { duration: Infinity, removeDelay: 400 }
            );

            throw err;
        });
}
