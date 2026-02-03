export default function formatDateToLocalInput(date) {
	if (!date) return "";

	let d;

	if (date instanceof Date) {
		d = date;
	} else if (typeof date === "string" || typeof date === "number") {
		d = new Date(date);
	} else {
		return "";
	}

	if (isNaN(d.getTime())) return "";

	const offset = d.getTimezoneOffset();
	const local = new Date(d.getTime() - offset * 60000);

	return local.toISOString().slice(0, 16); // YYYY-MM-DDTHH:mm
}
