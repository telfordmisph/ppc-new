export default function LoadingScreen({ text = "Loading..." }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-base-200 font-[Poppins]">
            <div className="flex flex-col items-center space-y-4">
                <span className="text-black loading loading-spinner loading-lg"></span>
                <span className="text-lg font-semibold text-black animate-fade">
                    {text}
                </span>
            </div>
        </div>
    );
}
