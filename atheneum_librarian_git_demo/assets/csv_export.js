(() => {
  document.addEventListener("click", async (event) => {
    const target = event.target instanceof Element ? event.target : event.target.parentElement;
    const link = target?.closest('a[href*="export_books.php"]');
    if (!link || !window.pywebview?.api?.save_csv) return;

    event.preventDefault();
    try {
      const response = await fetch(link.href, { cache: "no-store" });
      if (!response.ok) throw new Error("The CSV could not be generated.");
      const result = await window.pywebview.api.save_csv(await response.text());
      if (result.error) {
        window.alert(`CSV export failed: ${result.error}`);
      }
    } catch (error) {
      window.alert(`CSV export failed: ${error.message}`);
    }
  });
})();
