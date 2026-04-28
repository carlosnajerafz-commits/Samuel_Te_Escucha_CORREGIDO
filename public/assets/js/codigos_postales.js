document.addEventListener("DOMContentLoaded", () => {
  const cpInput = document.getElementById("codigo_postal");
  const coloniaInput = document.getElementById("colonia");

  if (!cpInput || !coloniaInput) return;

  cpInput.addEventListener("change", async () => {
    const cp = cpInput.value.trim();

    if (cp.length !== 5) {
      coloniaInput.value = "";
      return;
    }

    try {
      const res = await fetch(`codigos_postales.php?cp=${encodeURIComponent(cp)}`);
      const data = await res.json();

      if (data.length > 0) {
        coloniaInput.value = data[0].colonia;
      } else {
        coloniaInput.value = "";
      }
    } catch (e) {
      coloniaInput.value = "";
    }
  });
});