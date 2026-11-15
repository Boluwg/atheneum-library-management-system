(() => {
  const status = document.getElementById("scannerStatus");
  const input = document.getElementById("rfidInput");
  if (!status || !input) return;

  let desktopApp = navigator.userAgent.includes("AtheneumDesktop");
  try {
    desktopApp = desktopApp || window.sessionStorage.getItem("atheneum_desktop") === "1";
  } catch (error) {
    return;
  }
  if (!desktopApp) return;

  const bridgeUrl = "http://localhost:8765";
  const getSelectedPort = () => window.sessionStorage.getItem("atheneum_scanner_port") || "";

  const connectBridge = async () => {
    const port = getSelectedPort();
    if (!port) return false;
    try {
      const response = await fetch(`${bridgeUrl}/connect`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ port }),
      });
      if (!response.ok) throw new Error("Scanner is not connected");
      return true;
    } catch (error) {
      return false;
    }
  };

  const resetBridge = async () => {
    const response = await fetch(`${bridgeUrl}/reset`, { method: "POST" });
    if (!response.ok) throw new Error("Unable to prepare scanner");
  };

  const sleep = (milliseconds) => new Promise((resolve) => {
    window.setTimeout(resolve, milliseconds);
  });

  const watchForTag = async () => {
    while (true) {
      while (!(await connectBridge())) {
        status.textContent = "Waiting for scanner...";
        status.className = "scanner-status";
        await sleep(2000);
      }
      try {
        await resetBridge();
        status.textContent = "Ready - tap a tag";
        status.className = "scanner-status connected";
        const response = await fetch(`${bridgeUrl}/scan?timeout=60`, {
          cache: "no-store",
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || "Scanner failed");
        input.value = result.tag;
        input.dispatchEvent(new Event("input", { bubbles: true }));
        status.textContent = "Tag scanned";
        status.className = "scanner-status connected";
        return;
      } catch (error) {
        status.textContent = "Waiting for scanner...";
        status.className = "scanner-status";
        await sleep(2000);
      }
    }
  };

  void watchForTag();
})();
