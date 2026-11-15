(() => {
  const indicator = document.getElementById("globalScannerStatus");
  const isLoginPage = window.location.pathname.endsWith("/login.php");
  if (!indicator && !isLoginPage) return;

  const desktopQuery = new URLSearchParams(window.location.search).get("atheneum_desktop") === "1";
  let desktopApp = navigator.userAgent.includes("AtheneumDesktop") || desktopQuery;
  try {
    if (desktopQuery) window.sessionStorage.setItem("atheneum_desktop", "1");
    desktopApp = desktopApp || window.sessionStorage.getItem("atheneum_desktop") === "1";
  } catch (error) {
    // The user agent and URL query still identify the packaged app.
  }
  if (!desktopApp) return;

  const bridgeUrl = "http://localhost:8765";
  if (indicator) indicator.hidden = false;

  const getSelectedPort = () => {
    try {
      return window.sessionStorage.getItem("atheneum_scanner_port") || "";
    } catch (error) {
      return "";
    }
  };

  const setSelectedPort = (port) => {
    try {
      window.sessionStorage.setItem("atheneum_scanner_port", port);
    } catch (error) {
      return;
    }
  };

  const setStatus = (message, state) => {
    if (!indicator) return;
    indicator.textContent = message;
    indicator.className = `global-scanner-status ${state}`;
  };

  const connect = async (port) => {
    const response = await fetch(`${bridgeUrl}/connect`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ port }),
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "Scanner disconnected");
    return result;
  };

  const showPortPicker = async (required = false) => {
    const existing = document.getElementById("scannerPortModal");
    if (existing) return;

    const modal = document.createElement("div");
    modal.id = "scannerPortModal";
    modal.className = "scanner-port-modal";
    modal.innerHTML = `
      <div class="scanner-port-dialog" role="dialog" aria-modal="true"
           aria-labelledby="scannerPortTitle">
        <h2 id="scannerPortTitle">Select RFID scanner</h2>
        <p>Choose the COM port used by the Arduino PN532.</p>
        <select id="scannerPortSelect" aria-label="Scanner COM port"></select>
        <div id="scannerPortError" class="scanner-port-error"></div>
        <div class="scanner-port-actions">
          ${required ? "" : '<button type="button" id="scannerPortCancel">Cancel</button>'}
          <button type="button" id="scannerPortConfirm">Use this port</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    const select = modal.querySelector("#scannerPortSelect");
    const error = modal.querySelector("#scannerPortError");
    const confirm = modal.querySelector("#scannerPortConfirm");
    const cancel = modal.querySelector("#scannerPortCancel");

    try {
      const response = await fetch(`${bridgeUrl}/ports`, { cache: "no-store" });
      const result = await response.json();
      if (!response.ok || !Array.isArray(result.ports)) throw new Error("Cannot list COM ports");
      result.ports.forEach((port) => {
        const option = document.createElement("option");
        option.value = port.device;
        option.textContent = `${port.device} - ${port.description || "Serial device"}`;
        select.appendChild(option);
      });
      const selected = getSelectedPort();
      if (selected) select.value = selected;
      if (!result.ports.length) error.textContent = "No scanner ports found. Connect the Arduino and refresh.";
    } catch (requestError) {
      error.textContent = "The scanner bridge is not running yet.";
    }

    confirm.addEventListener("click", async () => {
      if (!select.value) {
        error.textContent = "Select a COM port first.";
        return;
      }
      confirm.disabled = true;
      try {
        const result = await connect(select.value);
        setSelectedPort(result.port);
        setStatus(`Scanner connected (${result.port})`, "connected");
        modal.remove();
      } catch (requestError) {
        error.textContent = requestError.message;
        confirm.disabled = false;
      }
    });
    if (cancel) cancel.addEventListener("click", () => modal.remove());
  };

  const updateStatus = async () => {
    const port = getSelectedPort();
    if (!port) {
      setStatus("Select scanner port", "reconnecting");
      return;
    }
    try {
      const result = await connect(port);
      setStatus(`Scanner connected (${result.port})`, "connected");
    } catch (error) {
      setStatus(`Scanner reconnecting (${port})...`, "reconnecting");
    }
  };

  if (indicator) {
    indicator.addEventListener("click", () => void showPortPicker(false));
    void updateStatus();
    window.setInterval(updateStatus, 2500);
  } else {
    void showPortPicker(true);
  }
})();
