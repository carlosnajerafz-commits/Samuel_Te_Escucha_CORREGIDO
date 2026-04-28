document.addEventListener("DOMContentLoaded", () => {
  const calendarTitle = document.getElementById("calendarTitle");
  const calendarGrid = document.getElementById("calendarGrid");
  const prevMonthBtn = document.getElementById("prevMonthBtn");
  const nextMonthBtn = document.getElementById("nextMonthBtn");
  const fechaInput = document.getElementById("fechaInput");
  const fechaVisible = document.getElementById("fechaVisible");
  const calendarSelectedInfo = document.getElementById("calendarSelectedInfo");
  const slotsGrid = document.getElementById("slotsGrid");
  const horaInput = document.getElementById("horaInput");
  const form = document.getElementById("citaForm");

  const meses = [
    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
  ];

  const diasLargos = [
    "Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"
  ];

  let currentDate = new Date();
  let selectedDate = null;

  function formatDateYYYYMMDD(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
  }

  function formatDateReadable(date) {
    const dayName = diasLargos[date.getDay()];
    const day = date.getDate();
    const month = meses[date.getMonth()];
    const year = date.getFullYear();
    return `${dayName} ${day} de ${month} de ${year}`;
  }

  function isPastDay(date) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const compare = new Date(date);
    compare.setHours(0, 0, 0, 0);

    return compare < today;
  }

  function isTuesday(date) {
    return date.getDay() === 2;
  }

  function clearHourSelection() {
    horaInput.value = "";
    document.querySelectorAll(".slot-btn").forEach(btn => {
      btn.classList.remove("active");
    });
  }

  function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    calendarTitle.textContent = `${meses[month]} ${year}`;
    calendarGrid.innerHTML = "";

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    let startDay = firstDay.getDay();
    startDay = startDay === 0 ? 6 : startDay - 1;

    for (let i = 0; i < startDay; i++) {
      const empty = document.createElement("div");
      empty.className = "calendar-day-ui empty";
      calendarGrid.appendChild(empty);
    }

    for (let day = 1; day <= lastDay.getDate(); day++) {
      const date = new Date(year, month, day);
      const button = document.createElement("button");
      button.type = "button";
      button.className = "calendar-day-ui";

      const available = isTuesday(date) && !isPastDay(date);
      if (!available) {
        button.classList.add("disabled");
      } else {
        button.classList.add("enabled");
      }

      if (
        selectedDate &&
        selectedDate.getFullYear() === date.getFullYear() &&
        selectedDate.getMonth() === date.getMonth() &&
        selectedDate.getDate() === date.getDate()
      ) {
        button.classList.add("selected");
      }

      button.innerHTML = `
        <span class="calendar-day-number">${day}</span>
        <span class="calendar-day-label">${isTuesday(date) ? "Martes" : ""}</span>
      `;

      button.addEventListener("click", () => {
        if (!available) return;

        selectedDate = date;
        fechaInput.value = formatDateYYYYMMDD(date);
        fechaVisible.value = formatDateReadable(date);
        calendarSelectedInfo.textContent = `Fecha seleccionada: ${formatDateReadable(date)}`;
        clearHourSelection();
        renderCalendar();
      });

      calendarGrid.appendChild(button);
    }
  }

  prevMonthBtn.addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
  });

  nextMonthBtn.addEventListener("click", () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
  });

  slotsGrid.addEventListener("click", (e) => {
    const btn = e.target.closest(".slot-btn");
    if (!btn) return;

    if (!fechaInput.value) {
      alert("Primero selecciona una fecha en el calendario.");
      return;
    }

    document.querySelectorAll(".slot-btn").forEach(slot => slot.classList.remove("active"));
    btn.classList.add("active");
    horaInput.value = btn.dataset.slot;
  });

  form.addEventListener("submit", (e) => {
    if (!fechaInput.value) {
      e.preventDefault();
      alert("Selecciona una fecha en el calendario.");
      return;
    }

    if (!horaInput.value) {
      e.preventDefault();
      alert("Selecciona un horario.");
      return;
    }
  });

  renderCalendar();
});