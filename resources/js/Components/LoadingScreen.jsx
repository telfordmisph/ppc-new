export default function LoadingScreen({ text = "Loading..." }) {
	return (
		<div className="bg-base-300 top-0 left-0 w-full h-full flex absolute items-center justify-center font-[Poppins]">
			<div className="flex flex-col items-center space-y-4">
				<div className="flex items-center space-x-4">
					<span className="loading loading-ring loading-xl"></span>
					<span className="text-[20pt] animate-fade">•••</span>
					<span className="loading loading-ring loading-xl"></span>
				</div>
				<span className="text-xl font-semibold animate-fade">{text}</span>
			</div>
		</div>
	);
}
