export default function Modal({
    id,
    title = "Modal Title",
    buttonText = "Open Modal",
    children,
    buttonClass = "",
    className = "",
}) {
    return (
        <>
            {/* Trigger Button */}
            <button
                className={buttonClass}
                onClick={() => document.getElementById(id).showModal()}
            >
                {buttonText}
            </button>

            {/* Modal Dialog */}
            <dialog id={id} className="modal">
                <div className={`modal-box ${className}`}>
                    <form method="dialog">
                        {/* Close Button */}
                        <button
                            className="absolute btn btn-sm btn-circle btn-ghost right-2 top-2"
                            onClick={() => document.getElementById(id)?.close()}
                        >
                            ✕
                        </button>

                        {/* Modal Title */}
                        <h3 className="text-lg font-bold">{title}</h3>

                        {/* Modal Content */}
                        <div className="pt-4">{children}</div>
                    </form>
                </div>
            </dialog>
        </>
    );
}
