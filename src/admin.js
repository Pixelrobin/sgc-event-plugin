import flatpickr from 'flatpickr';
import '../node_modules/flatpickr/dist/flatpickr.min.css';
import './style.css';

var SGCEventAdmin = function() {
	var date = document.getElementById('sgc-event-date');

	function inputToggle(checkbox, input) {
		function handleToggle() {
			if (checkbox.checked) {
				input.disabled = false;
				input.style.display = 'initial';
			} else {
				input.disabled = true;
				input.style.display = 'none';
			}
		}

		handleToggle();

		checkbox.addEventListener('click', handleToggle);
	}

	inputToggle(
		document.getElementById('sgc-event-include-start-time'),
		document.getElementById('sgc-event-start-time')
	);

	inputToggle(
		document.getElementById('sgc-event-include-end-time'),
		document.getElementById('sgc-event-end-time')
	);

	flatpickr('.sgc-event-date-input', {
		dateFormat: 'Y-m-d'
	});

	flatpickr('.sgc-event-time-input', {
		enableTime: true,
		noCalendar: true,
		dateFormat: 'H:i:S'
	});

	console.log('hello');
}();