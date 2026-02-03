import { Link } from "@inertiajs/react";
import { BiSolidErrorCircle } from "react-icons/bi";

export default function NotFound() {
	return (
		<div className="flex flex-col text-center items-center justify-center h-180 my-auto px-6">
			<BiSolidErrorCircle className="text-9xl text-red-500" />
			<h1 className="text-[60pt] font-bold text-gray-800 dark:text-gray-100">
				404
			</h1>
			<p className="text-2xl font-bold text-gray-600 dark:text-gray-300">
				Page not found.
			</p>
			<p className="text-lg text-gray-500 dark:text-gray-400">
				Sorry, the page you are looking for doesn’t exist or has been moved.
			</p>

			<Link
				href={route("dashboard")}
				className="inline-block px-6 py-2 mt-3 text-lg font-semibold text-secondary"
			>
				Go Back
			</Link>
		</div>
	);
}
