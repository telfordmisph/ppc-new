function getColorForMachine(machineId) {
	// Convert machineId (number or string) to a number hash
	let hash = 0;
	const str = machineId.toString();
	for (let i = 0; i < str.length; i++) {
		hash = str.charCodeAt(i) + ((hash << 5) - hash);
	}
	const hue = hash % 360; // HSL hue 0-359
	return { hue, color: `hsl(${hue}, 70%, 50%)` }; // returns hue + color
}

export default getColorForMachine;

function getBackgroundForMachine(machineId, textLightness = 20) {
	const { hue } = getColorForMachine(machineId);

	// Background lightness is opposite of text lightness
	const bgLightness = textLightness > 50 ? 20 : 90; // dark text → light bg, light text → dark bg
	return `hsl(${hue}, 30%, ${bgLightness}%)`;
}

export { getBackgroundForMachine };
