(() => {
  const key = "atheneum-theme";
  const saved = localStorage.getItem(key);
  if (saved === "light") document.documentElement.dataset.theme = "light";

  document.querySelectorAll("[data-theme-toggle]").forEach((button) => {
    const update = () => {
      const light = document.documentElement.dataset.theme === "light";
      button.textContent = light ? "☾ Dark" : "☀ Light";
      button.setAttribute("aria-label", light ? "Switch to dark mode" : "Switch to light mode");
    };
    button.addEventListener("click", () => {
      const light = document.documentElement.dataset.theme === "light";
      document.documentElement.dataset.theme = light ? "dark" : "light";
      localStorage.setItem(key, light ? "dark" : "light");
      update();
    });
    update();
  });
})();
